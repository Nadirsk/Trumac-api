<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DecryptIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Get the encrypted data from the request
            $encryptedData = $request->route('id');

            if (!$encryptedData) {
                return response()->json([
                    'message' => 'Missing encrypted ID parameter.',
                ], 400);
            }
            // var_dump($encryptedData);
            $decodedEncryptedData = str_replace(['-', '_'], ['+', '/'], urldecode($encryptedData));
            // Define your secret key and initialization vector (IV)
            $secretKey = '12345678123456781234567812345678';
            $iv = 'Ef7ix7ETPgghl3vP';
            // Decrypt the data
            $decryptedData = openssl_decrypt(base64_decode($decodedEncryptedData), 'AES-256-CBC', $secretKey, OPENSSL_RAW_DATA, $iv);
            if ($decryptedData === false) {
                return response()->json([
                    'encryptedData' => $encryptedData,
                    'message' => 'Decryption failed.',
                ], 400);
            }
            // Return the decrypted data
            $request->merge(['id' => $decryptedData]);
        } catch (DecryptException $e) {
            return response()->json([
                'encryptedData' => $encryptedData,
                'message' => 'Decryption process failed.',
            ], 400);
        }
        return $next($request);
    }
}

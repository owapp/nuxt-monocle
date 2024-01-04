<?php

namespace App\Http\Controllers;

use App\Services\MonocleService;
use Illuminate\Http\Request;

class MonocleController extends Controller
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    private $request;
    private $monocleService;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(Request $request, MonocleService $monocleService) {
        $this->request = $request;
        $this->monocleService = $monocleService;
    }

    /**
     * Verify if user is in anon mode
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyAnonMode() {
      // Validate request
        $this->validate($this->request, [
            'monocle_bundle' => 'required|string',
        ]);

        try {
            // Retrieve Ip from request
            $ip = getClientIp();

            // Anon détection
            $decryptedData = $this->monocleService->decrypt($this->request->monocle_bundle);
            $anonFields = $this->monocleService->checkAnonymizedSession($decryptedData, $ip);
            $is_anon_session = $anonFields['is_anon_session'];
            $anon_type = $anonFields['anon_type'];

            // Check if is VPN request and if user confirm
            if ($is_anon_session) {
                return response()->json([
                    'message' => 'You are not in anon mode.'
                ], 422);
            }

            // Send response
            return response()->json([
                'message' => 'You are not in anon mode'
            ], 200);

        } catch (\Throwable $exception) {
          throw $exception;
        }
    }
}

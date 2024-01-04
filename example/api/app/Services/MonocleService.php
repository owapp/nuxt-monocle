<!--
  web-token/jwt-framework is required for this service to work.
  Install it with `composer require web-token/jwt-framework`.
-->

<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use Jose\Component\Core\JWK;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\JWETokenSupport;
use Jose\Component\Encryption\Serializer\JWESerializerManager;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\Algorithm\KeyEncryption\ECDHES;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\KeyManagement\KeyConverter\KeyConverter;

class MonocleService
{
    private $jweDecrypter;
    private $serializerManager;
    private $headerCheckerManager;
    private $jwk;

    /**
     * Create a new service instance.
     *
     * @param string $privateKey
     * @throws Exception
     */
    public function __construct(string $privateKey)
    {
        // Initializes algorithm managers
        $keyEncryptionAlgorithmManager = new AlgorithmManager([new ECDHES()]);
        $contentEncryptionAlgorithmManager = new AlgorithmManager([new A256GCM()]);
        $compressionMethodManager = new CompressionMethodManager([new Deflate()]);

        // Initializes the JWEDecrypter
        $this->jweDecrypter = new JWEDecrypter(
            $keyEncryptionAlgorithmManager,
            $contentEncryptionAlgorithmManager,
            $compressionMethodManager
        );

        // Initializes the JWESerializerManager
        $this->serializerManager = new JWESerializerManager([new CompactSerializer()]);

        // Initializes the HeaderCheckerManager
        $this->headerCheckerManager = new HeaderCheckerManager(
            [new AlgorithmChecker(['ECDH-ES'])],
            [new JWETokenSupport()]
        );

        // Load private key
        $key = KeyConverter::loadFromKey($privateKey);
        $this->jwk = new JWK($key);
    }

    /**
     * Get the type of IP address.
     *
     * @param string $ip
     * @return string|bool
     */
    private static function getIpType($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return 'IPv4';
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return 'IPv6';
        } else {
            return false;
        }
    }

    /**
     * Decrypt the threat bundle.
     *
     * @param string $threatBundle
     * @return string
     * @throws Exception
     */
    public function decrypt($threatBundle)
    {
        // Remove line breaks from bundle (if necessary)
        $threatBundle = str_replace(["\r", "\n"], "", $threatBundle);

        // Deserialize the JWE
        $jwe = $this->serializerManager->unserialize($threatBundle);

        // Optionally checks headers
        $this->headerCheckerManager->check($jwe, 0);

        // Decrypt the token
        if ($this->jweDecrypter->decryptUsingKey($jwe, $this->jwk, 0)) {
            return $jwe->getPayload();
        }

        // Throw an exception if the token cannot be decrypted
        return throw new \Exception('Error decrypting threat bundle.');
    }

    /**
     * Decode the decrypted data.
     *
     * @param string $decryptedData
     * @return array
     * @throws Exception
     */
    static function decode($decryptedData) {
        if (!$decryptedData) {
            throw new \Exception('Decrypted data is required.');
        }

        // Decode JSON string to PHP array
        $dataArray = json_decode($decryptedData, true);

        // Check if a JSON decoding error occurred
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Monocle Error: Error decoding JSON.');
        }

        // Check the value of complete. Under normal operation, this will be true.
        if (empty($dataArray)) {
            throw new \Exception('Monocle Error: Incomplete data.');
        }

        return $dataArray;
    }

    /**
     * Check if the session is anonymized.
     *
     * @param string $decryptedData
     * @param string $ip
     * @return array
     * @throws Exception
     */
    function checkAnonymizedSession($decryptedData, $ip)
    {
        if (!$decryptedData) {
            throw new \Exception('Monocle Error: Decrypted bundle is required.');
        }
        if (!$ip) {
            throw new \Exception('Monocle Error: IP is required.');
        }

        $anon_type = null;
        $anon_session = 0;
        $justifications = [];

        // Decode JSON string to PHP array
        $dataArray = $this->decode($decryptedData);

        // Check the value of complete. Under normal operation, this will be true.
        // A false value indicates Monocle wasn't able to complete its evaluation,
        // suggesting a higher possibility of false positives/negatives
        if (!isset($dataArray['complete']) || $dataArray['complete'] !== true) {
            $anon_type = 'incomplete';
            $justifications[] = 'Incomplete data.';
            $anon_session = 1;
        }

        // Verify that the IP address matches
        if (!isset($dataArray['ip']) || $dataArray['ip'] !== $ip) {
            // Get types for each IP address
            $typeDataArrayIp = $this->getIpType($dataArray['ip']);
            $typeIp = $this->getIpType($ip);

            if (!$typeDataArrayIp || !$typeIp) {
                // TODO: Handle this case
            } else if ($typeDataArrayIp === $typeIp) {
                $anon_type = 'ip';
                $justifications[] = 'Address IP not match - Monocle: ' . $dataArray['ip'] . '; Request: ' . $ip;
                $anon_session = 1;
            }
        }

        // Check that the timestamp is within an acceptable range (~10 minutes)
        if (isset($dataArray['ts'])) {
            $timestamp = Carbon::parse($dataArray['ts']);
            $oldestTime = Carbon::now()->subMinutes(10);
            if ($timestamp->lt($oldestTime)) {
                $anon_type = 'timestamp';
                $justifications[] = 'Timestamp out of range: ' . $timestamp . '. Oldest time is: ' . $oldestTime;
                $anon_session = 1;
            }
        }

        // Check if the session is anonymized
        if (isset($dataArray['anon']) && $dataArray['anon'] === true) {
            if (isset($dataArray['proxied']) && $dataArray['proxied'] === true) {
                $anon_type = 'proxy';
            }
            if (isset($dataArray['vpn']) && $dataArray['vpn'] === true) {
              // Overide proxy if vpn is detected
              $anon_type = 'vpn';
            }
            $justifications[] = 'Anonymized session.';
            $anon_session = 1;
        }

        // Return the results
        return [
            'is_anon_session' => $anon_session,
            'anon_type' => $anon_type,
            'justifications' => $justifications.
        ];

    }

}

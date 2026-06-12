<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SOAPAuditService
{
    /**
     * Send critical transaction log to Legacy SOAP Audit Server.
     */
    public function sendAuditLog(string $activityName, array $transactionData): ?string
    {
        $teamId = env('TEAM_ID', 'TEAM-12');
        $soapUrl = env('SOAP_AUDIT_URL', 'https://iae-sso.virtualfri.id/soap/v1/audit');
        $apiKey = env('SSO_API_KEY', 'KEY-MHS-314');

        // 1. Get bearer token for SSO Auth M2M
        $token = $this->getSSOToken($apiKey);
        if (!$token) {
            Log::error("SOAP Audit: Failed to retrieve M2M SSO Token.");
            return null;
        }

        // 2. Format JSON payload and wrap in rigid SOAP XML Envelope
        $jsonData = json_encode($transactionData);
        $xmlPayload = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit">
  <soap:Body>
    <iae:AuditRequest>
      <iae:TeamID>{$teamId}</iae:TeamID>
      <iae:ActivityName>{$activityName}</iae:ActivityName>
      <iae:LogContent><![CDATA[{$jsonData}]]></iae:LogContent>
    </iae:AuditRequest>
  </soap:Body>
</soap:Envelope>
XML;

        try {
            // 3. Post to SOAP Audit endpoint
            $response = Http::timeout(15)->retry(1, 500)->withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'Authorization' => 'Bearer ' . $token,
            ])->withBody($xmlPayload, 'text/xml')
              ->post($soapUrl);

            if ($response->failed()) {
                Log::error("SOAP Audit Request Failed. HTTP Status: " . $response->status() . " Response: " . $response->body());
                return null;
            }

            $responseBody = $response->body();

            // 4. Parse XML Response
            // Remove namespaces for easier SimpleXML parsing
            $cleanXml = str_ireplace(['soap:', 'iae:'], '', $responseBody);
            $xmlElement = simplexml_load_string($cleanXml);

            if ($xmlElement === false) {
                Log::error("SOAP Audit: Failed to parse XML response body.");
                return null;
            }

            // Extract status and ReceiptNumber
            $status = (string) ($xmlElement->Body->AuditResponse->Status ?? '');
            if (!$status && isset($xmlElement->Body->AuditRequestResponse)) {
                $status = (string) ($xmlElement->Body->AuditRequestResponse->Status ?? '');
            }
            
            $receiptNumber = (string) ($xmlElement->Body->AuditResponse->ReceiptNumber ?? '');
            if (!$receiptNumber && isset($xmlElement->Body->AuditRequestResponse)) {
                $receiptNumber = (string) ($xmlElement->Body->AuditRequestResponse->ReceiptNumber ?? '');
            }

            // Fallback parsing if structure is different
            if (!$receiptNumber) {
                if (preg_match('/<iae:ReceiptNumber>(.*?)<\/iae:ReceiptNumber>/', $responseBody, $matches)) {
                    $receiptNumber = $matches[1];
                }
                if (preg_match('/<iae:Status>(.*?)<\/iae:Status>/', $responseBody, $matches)) {
                    $status = $matches[1];
                }
            }

            if (strtoupper($status) === 'SUCCESS' || $receiptNumber) {
                Log::info("SOAP Audit: Success. ReceiptNumber: " . $receiptNumber);
                return $receiptNumber;
            }

            Log::error("SOAP Audit: Server response status not SUCCESS. Response: " . $responseBody);
            return null;

        } catch (\Exception $e) {
            Log::error("SOAP Audit Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Request SSO Token using the student M2M API Key.
     */
    private function getSSOToken(string $apiKey): ?string
    {
        try {
            $response = Http::timeout(10)->retry(1, 500)->post('https://iae-sso.virtualfri.id/api/v1/auth/token', [
                'api_key' => $apiKey
            ]);

            if ($response->successful()) {
                return $response->json('token') ?? $response->json('data.token');
            }
            
            Log::error("SSO Token Request Failed: " . $response->body());
        } catch (\Exception $e) {
            Log::error("SSO Token Exception: " . $e->getMessage());
        }
        return null;
    }
}

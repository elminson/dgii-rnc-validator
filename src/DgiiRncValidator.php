<?php

declare(strict_types=1);

namespace Seisigma\DgiiRncValidator;

use Seisigma\DgiiRncValidator\Helpers\Status;
use Seisigma\DgiiRncValidator\Helpers\Types;
use Seisigma\DgiiRncValidator\Helpers\Utils;
use SoapClient;

class DgiiRncValidator
{
    private string $rnc;

    public static function validateRNC(string $string): bool
    {
        $cleanedId = Utils::getNumbers($string);
        preg_match('/^(\d{9}|\d{11})$/', $cleanedId, $matches);

        return (bool) count($matches);
    }

    public static function rncType(string $string): bool | string
    {
        if (self::validateRNC($string)) {
            return (strlen($string) === 9) ? Types::RNC->toString() : Types::CEDULA->toString();
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    public static function check(string $id): array|bool
    {
        if (! DgiiRncValidator::validateRNC($id)) {
            throw new \Exception('Provide a valid id.');
        }

        $client = new SoapClient('https://dgii.gov.do/wsMovilDGII/WSMovilDGII.asmx?wsdl');

        $params = [
            'value' => $id,
            'patronBusqueda' => 0,
            'inicioFilas' => 0,
            'filaFilas' => 10,
            'IMEI' => '',
        ];

        $response = $client->__soapCall('GetContribuyentes', [$params]);

        if (! $response->GetContribuyentesResult) {
            return false;
        }

        $results = [
            'RGE_NOMBRE' => $name,
            'NOMBRE_COMERCIAL' => $commercialName,
            'ESTATUS' => $status
        ] = json_decode($response->GetContribuyentesResult, true);

        return [
            'rnc' => $id,
            'name' => $name,
            'commercial_name' => $commercialName,
            'status' => Status::from((int)$status)->toString(),
        ];
    }
}

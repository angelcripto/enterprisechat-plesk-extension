<?php
/**
 * Lee y escribe /opt/enterprisechat/appsettings.Production.json sin pisar
 * claves que la extensión no gestiona. Útil para que la UI permita rotar la
 * SigningKey, ajustar el `AccessTokenLifetimeMinutes` o registrar la
 * `LicenseKey` antes de llamar a /license/activate.
 *
 * Las escrituras pasan por pm_ApiCli + filemng porque el archivo es propiedad
 * de `enterprisechat:enterprisechat` (modo 0640) y el usuario `psaadm` no
 * puede tocarlo directamente.
 */
class Modules_Enterprisechat_AppSettings
{
    const FILE = '/opt/enterprisechat/appsettings.Production.json';

    public static function load(): array
    {
        if (!is_readable(self::FILE)) {
            $raw = self::readViaSbin();
        } else {
            $raw = (string)@file_get_contents(self::FILE);
        }
        if ($raw === '' || $raw === false) {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public static function save(array $data): void
    {
        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        if ($json === false) {
            throw new pm_Exception('No se pudo serializar appsettings.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'ecsettings_');
        file_put_contents($tmp, $json);
        pm_ApiCli::callSbin(
            'filemng',
            ['enterprisechat', 'cp2perm', $tmp, self::FILE, '0640']
        );
        @unlink($tmp);
    }

    /**
     * Helper para setear/leer rutas tipo "EnterpriseChat:Jwt:SigningKey".
     */
    public static function set(string $colonPath, $value): void
    {
        $data = self::load();
        $ref =& $data;
        foreach (explode(':', $colonPath) as $key) {
            if (!isset($ref[$key]) || !is_array($ref[$key])) {
                $ref[$key] = [];
            }
            $ref =& $ref[$key];
        }
        $ref = $value;
        self::save($data);
    }

    public static function get(string $colonPath, $default = null)
    {
        $ref = self::load();
        foreach (explode(':', $colonPath) as $key) {
            if (!is_array($ref) || !array_key_exists($key, $ref)) {
                return $default;
            }
            $ref = $ref[$key];
        }
        return $ref;
    }

    private static function readViaSbin(): string
    {
        $r = pm_ApiCli::callSbin(
            'filemng',
            ['enterprisechat', 'cat', self::FILE],
            pm_ApiCli::RESULT_FULL
        );
        return (string)($r['stdout'] ?? '');
    }
}

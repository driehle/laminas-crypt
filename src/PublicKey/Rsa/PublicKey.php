<?php

/**
 * @see       https://github.com/laminas/laminas-crypt for the canonical source repository
 * @copyright https://github.com/laminas/laminas-crypt/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-crypt/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Crypt\PublicKey\Rsa;

/**
 * RSA public key
 *
 * @category    Laminas
 * @package     Laminas_Crypt
 * @subpackage  PublicKey
 */
class PublicKey extends AbstractKey
{
    const CERT_START = '-----BEGIN CERTIFICATE-----';

    /**
     * @var string
     */
    protected $certificateString = null;

    /**
     * Create public key instance public key from PEM formatted key file
     * or X.509 certificate file
     *
     * @param  string      $pemOrCertificateFile
     * @return PublicKey
     * @throws Exception\InvalidArgumentException
     */
    public static function fromFile($pemOrCertificateFile)
    {
        if (!is_readable($pemOrCertificateFile)) {
            throw new Exception\InvalidArgumentException(
                "File '{$pemOrCertificateFile}' is not readable"
            );
        }

        return new static(file_get_contents($pemOrCertificateFile));
    }

    /**
     * Construct public key with PEM formatted string or X.509 certificate
     *
     * @param  string $pemStringOrCertificate
     * @throws Exception\RuntimeException
     */
    public function __construct($pemStringOrCertificate)
    {
        $result = openssl_pkey_get_public($pemStringOrCertificate);
        if (false === $result) {
            throw new Exception\RuntimeException(
                'Unable to load public key; openssl ' . openssl_error_string()
            );
        }

        if (strpos($pemStringOrCertificate, self::CERT_START) !== false) {
            $this->certificateString = $pemStringOrCertificate;
        } else {
            $this->pemString = $pemStringOrCertificate;
        }

        $this->opensslKeyResource = $result;
        $this->details            = openssl_pkey_get_details($this->opensslKeyResource);
    }

    /**
     * Encrypt using this key
     *
     * @param  string $data
     * @return string
     * @throws Exception\RuntimeException
     */
    public function encrypt($data)
    {
        if (empty($data)) {
            throw new Exception\InvalidArgumentException('The data to encrypt cannot be empty');
        }

        $encrypted = '';
        $result = openssl_public_encrypt($data, $encrypted, $this->getOpensslKeyResource());
        if (false === $result) {
            throw new Exception\RuntimeException(
                'Can not encrypt; openssl ' . openssl_error_string()
            );
        }

        return $encrypted;
    }


    /**
     * Decrypt using this key
     *
     * @param  string $data
     * @return string
     * @throws Exception\RuntimeException
     */
    public function decrypt($data)
    {
        if (!is_string($data)) {
            throw new Exception\InvalidArgumentException('The data to decrypt must be a string');
        }
        if ('' === $data) {
            throw new Exception\InvalidArgumentException('The data to decrypt cannot be empty');
        }

        $decrypted = '';
        $result = openssl_public_decrypt($data, $decrypted, $this->getOpensslKeyResource());
        if (false === $result) {
            throw new Exception\RuntimeException(
                'Can not decrypt; openssl ' . openssl_error_string()
            );
        }

        return $decrypted;
    }

    /**
     * Get certificate string
     *
     * @return string
     */
    public function getCertificate()
    {
        return $this->certificateString;
    }

    /**
     * To string
     *
     * @return string
     * @throws Exception\RuntimeException
     */
    public function toString()
    {
        if (!empty($this->certificateString)) {
            return $this->certificateString;
        } elseif (!empty($this->pemString)) {
            return $this->pemString;
        }
        throw new Exception\RuntimeException('No public key string representation is available');
    }
}

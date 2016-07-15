<?php
/**
 * irmo Project ${PROJECT_URL}
 *
 * @link      ${GITHUB_URL} Source code
 */

namespace Sta\Commons;

class CryptQueryParam
{
    private $cryptKey;

    /**
     * @var \Zend\Crypt\BlockCipher
     */
    protected $blockCipher;

    /**
     * @var CryptQueryParam
     */
    protected static $instance;

    /**
     * CryptQueryParam constructor.
     *
     * @param string $cryptKey
     */
    public function __construct($cryptKey = 'Ba9t2NX5Lwuskpdh')
    {
        $this->cryptKey = $cryptKey;
    }


    /**
     * @return CryptQueryParam
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param $value
     * @return string
     * @see \App\Util\CryptQueryParam::crypt()
     */
    public static function crypt_($value)
    {
        return self::getInstance()->crypt($value);
    }

    /**
     * @param $value
     * @return mixed|null
     * @see \App\Util\CryptQueryParam::decrypt()
     */
    public static function decrypt_($value)
    {
        return self::getInstance()->decrypt($value);
    }
    
    /**
     * Recebe um valor e criptografa ele para ser usado em um parâmetro GET da URL.
     *
     * @param mixin $value
     *      Valor que será setado em um parametro GET.
     *      Qualquer coisa que possa ser convertida para JSON
     *
     * @return string
     *      O valor criptografado para setar na URL.
     */
    public function crypt($value)
    {
        $encryptedValue = rawurlencode($this->_getBlockCipher()->encrypt(json_encode($value)));

        $maxChars = 800;
        if (($strLen = strlen($encryptedValue)) > $maxChars) {
            trigger_error(
                "The encrypted value is bigger than $maxChars chars (it has $strLen chars). Try make it smaller to " .
                "avoid problems with big URLs. See more: http://www.boutell.com/newfaq/misc/urllength.html"
            );
        }

        return $encryptedValue;
    }

    /**
     * Retorna o valor do parametro ou null se não for possivel descriptografar.
     * @param $paramValue
     * @return mixed|null
     */
    public function decrypt($paramValue)
    {
        $result = null;
        $value  = $this->_getBlockCipher()->decrypt(rawurldecode($paramValue));

        if ($value) {
            try {
                $result = json_decode($value, JSON_OBJECT_AS_ARRAY);
            } catch (\Exception $e) {
                $result = null;
            }
        }

        return $result;
    }

    /**
     * @return \Zend\Crypt\BlockCipher
     */
    private function _getBlockCipher()
    {
        if (!$this->blockCipher) {
            $blockCipher = \Zend\Crypt\BlockCipher::factory('mcrypt');
            $blockCipher->setKey($this->cryptKey);

            $this->blockCipher = $blockCipher;
        }

        return $this->blockCipher;
    }
} 

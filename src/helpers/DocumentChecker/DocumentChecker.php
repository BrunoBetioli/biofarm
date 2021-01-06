<?php

namespace helpers\DocumentChecker;
/**
 * DocumentChecker valida e formata CPF e CNPJ
 *
 * Exemplo de uso:
 * $cpf_cnpj  = new DocumentChecker('71569042000196');
 * $formated = $cpf_cnpj->format(); // 71.569.042/0001-96
 * $check    = $cpf_cnpj->validate(); // True -> Válido
 *
 */

class DocumentChecker
{
    public $value;
    /** 
    * Configura o valor (Construtor)
    * 
    * Remove caracteres inválidos do CPF ou CNPJ
    * 
    * @param string $value - O CPF ou CNPJ
    */
    function __construct ($value = null)
    {
        /* Deixa apenas números no valor */
        $this->value = preg_replace('/[^0-9]/', '', $value);

        /* Garante que o value é uma string */
        $this->value = (string) $this->value;
    }
 
    /**
     * Verifica se é CPF ou CNPJ
     * 
     * Se for CPF tem 11 caracteres, CNPJ tem 14
     * 
     * @access public
     * @return string CPF, CNPJ ou false
     */
    public function check_cpf_cnpj ()
    {
        /* Verifica CPF */
        if (strlen($this->value) === 11) {
            return 'CPF';
        } 
        /* Verifica CNPJ */
        elseif (strlen($this->value) === 14) {
            return 'CNPJ';
        } 
        /* Não retorna nada */
        else {
            return false;
        }
    }
    
    /**
     * Verifica se todos os números são iguais
     * * 
     * @access protected
     * @return bool true para todos iguais, false para números que podem ser válidos
     */
    protected function check_equality()
    {
        /* Todos os caracteres em um array */
        $characters = str_split($this->value);
        
        /* Considera que todos os números são iguais */
        $same_values = true;
        
        /* Primeiro caractere */
        $last_val = $characters[0];
        
        /* Verifica todos os caracteres para detectar diferença */
        foreach($characters as $val) {
            
            /* Se o último valor for diferente do anterior, já temos
               um número diferente no CPF ou CNPJ */
            if ($last_val != $val) {
                $same_values = false; 
            }
            
            /* Grava o último número checado */
            $last_val = $val;
        }
        
        /* Retorna true para todos os números iguais
           ou falso para todos os números diferentes */
        return $same_values;
    }
 
    /**
     * Multiplica dígitos vezes posições
     *
     * @access protected
     * @param  string    $digitos      Os digitos desejados
     * @param  int       $posicoes     A posição que vai iniciar a regressão
     * @param  int       $soma_digitos A soma das multiplicações entre posições e dígitos
     * @return int                     Os dígitos enviados concatenados com o último dígito
     */
    protected function calc_position_digits($digits, $positions = 10, $sum_digits = 0)
    {
        /**
         *  Faz a soma dos dígitos com a posição
         *  Ex. para 10 posições:
         *    0    2    5    4    6    2    8    8   4
         *  x10   x9   x8   x7   x6   x5   x4   x3  x2
         *    0 + 18 + 40 + 28 + 36 + 10 + 32 + 24 + 8 = 196
         */
        for ($i = 0; $i < strlen($digits); $i++) {
            /* Preenche a soma com o dígito vezes a posição */
            $sum_digits = $sum_digits + ($digits[$i] * $positions);

            /* Subtrai 1 da posição */
            $positions--;

            /* Parte específica para CNPJ */
            /* Ex.: 5-4-3-2-9-8-7-6-5-4-3-2 */
            if ($positions < 2) {
                /* Retorno a posição para 9 */
                $positions = 9;
            }
        }

        /* Captura o resto da divisão entre $soma_digitos dividido por 11 */
        /* Ex.: 196 % 11 = 9 */
        $sum_digits = $sum_digits % 11;

        /* Verifica se $soma_digitos é menor que 2 */
        if ($sum_digits < 2) {
            /* $soma_digitos agora será zero */
            $sum_digits = 0;
        } else {
            /* Se for maior que 2, o resultado é 11 menos $soma_digitos */
            /* Ex.: 11 - 9 = 2 */
            /* Nosso dígito procurado é 2 */
            $sum_digits = 11 - $sum_digits;
        }

        /* Concatena mais um dígito aos primeiro nove dígitos */
        /* Ex.: 025462884 + 2 = 0254628842 */
        $doc = $digits . $sum_digits;

        /* Retorna */
        return $doc;
    }
 
    /**
     * Valida CPF
     *
     * @access protected
     * @param  string    $cpf O CPF com ou sem pontos e traço
     * @return bool           True para CPF correto - False para CPF incorreto
     */
    protected function validate_cpf()
    {
        /* Captura os 9 primeiros dígitos do CPF
           Ex.: 02546288423 = 025462884 */
        $digits = substr($this->value, 0, 9);

        /* Faz o cálculo dos 9 primeiros dígitos do CPF para obter o primeiro dígito */
        $new_cpf = $this->calc_position_digits($digits);

        /* Faz o cálculo dos 10 dígitos do CPF para obter o último dígito */
        $new_cpf = $this->calc_position_digits($new_cpf, 11);

        /* Verifica se todos os números são iguais */
        if ($this->check_equality()) {
            return false;
        }

        /* Verifica se o novo CPF gerado é idêntico ao CPF enviado */
        if ($new_cpf === $this->value) {
            /* CPF válido */
            return true;
        } else {
            /* CPF inválido */
            return false;
        }
    }
 
    /**
     * Valida CNPJ
     *
     * @access protected
     * @param  string     $cnpj
     * @return bool             true para CNPJ correto
     */
    protected function validate_cnpj ()
    {
        /* O valor original */
        $original_cnpj = $this->value;

        /* Captura os primeiros 12 números do CNPJ */
        $cnpj_firsts_numbers = substr($this->value, 0, 12);

        /* Faz o primeiro cálculo */
        $first_calc = $this->calc_position_digits($cnpj_firsts_numbers, 5);

        /* O segundo cálculo é a mesma coisa do primeiro, porém, começa na posição 6 */
        $second_calc = $this->calc_position_digits($first_calc, 6);

        /* Concatena o segundo dígito ao CNPJ */
        $cnpj = $second_calc;

        /* Verifica se todos os números são iguais */
        if ($this->check_equality()) {
            return false;
        }

        /* Verifica se o CNPJ gerado é idêntico ao enviado */
        if ($cnpj === $original_cnpj) {
            return true;
        }
    }
 
    /**
     * Valida
     * 
     * Valida o CPF ou CNPJ
     * 
     * @access public
     * @return bool      True para válido, false para inválido
     */
    public function validate()
    {
        /* Valida CPF */
        if ($this->check_cpf_cnpj() === 'CPF') {
            /* Retorna true para cpf válido */
            return $this->validate_cpf();
        } 
        /* Valida CNPJ */
        elseif ($this->check_cpf_cnpj() === 'CNPJ') {
            /* Retorna true para CNPJ válido */
            return $this->validate_cnpj();
        } 
        /* Não retorna nada */
        else {
            return false;
        }
    }
 
    /**
     * Formata CPF ou CNPJ
     *
     * @access public
     * @return string  CPF ou CNPJ formatado
     */
    public function format()
    {
        /* O valor formatado */
        $formated = false;

        /* Valida CPF */
        if ($this->check_cpf_cnpj() === 'CPF') {
            /* Verifica se o CPF é válido */
            if ($this->validate_cpf()) {
                /* Formata o CPF ###.###.###-## */
                $formated  = substr($this->value, 0, 3) . '.';
                $formated .= substr($this->value, 3, 3) . '.';
                $formated .= substr($this->value, 6, 3) . '-';
                $formated .= substr($this->value, 9, 2) . '';
            }
        } 
        /* Valida CNPJ */
        elseif ($this->check_cpf_cnpj() === 'CNPJ') {
            /* Verifica se o CPF é válido */
            if ( $this->validate_cnpj() ) {
                /* Formata o CNPJ ##.###.###/####-## */
                $formated  = substr($this->value,  0,  2) . '.';
                $formated .= substr($this->value,  2,  3) . '.';
                $formated .= substr($this->value,  5,  3) . '/';
                $formated .= substr($this->value,  8,  4) . '-';
                $formated .= substr($this->value, 12, 14) . '';
            }
        }

        /* Retorna o valor */
        return $formated;
    }
}
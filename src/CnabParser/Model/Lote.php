<?php
// Copyright (c) 2016 Glauber Portella <glauberportella@gmail.com>

// Permission is hereby granted, free of charge, to any person obtaining a
// copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation
// the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the
// Software is furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
// FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
// DEALINGS IN THE SOFTWARE.

namespace CnabParser\Model;

class Lote implements \JsonSerializable
{
    public $sequencial;
    public $header;
    public $trailer;
    public $detalhes;

    protected $layout;

    public function __construct(array $layout, $sequencial = 1)
    {
        $this->layout = $layout;

        $this->sequencial = $sequencial;
        // inicia com header e trailer = null pois cnab400 pode não conter header e trailer de lotes (CEF SIGCB CNAB400 por exemplo)
        $this->header = null;
        $this->trailer = null;
        $this->detalhes = array();

        if (isset($this->layout['header_lote'])) {
            $this->header = new HeaderLote();
            foreach ($this->layout['header_lote'] as $field => $definition) {
                $this->header->$field = (isset($definition['default'])) ? $definition['default'] : '';
            }
        }

        if (isset($this->layout['trailer_lote'])) {
            $this->trailer = new TrailerLote();
            foreach ($this->layout['trailer_lote'] as $field => $definition) {
                $this->trailer->$field = (isset($definition['default'])) ? $definition['default'] : '';
            }
        }
    }

    public function getLayout()
    {
        return $this->layout;
    }

    public function novoDetalhe(array $excetoSegmentos = array())
    {
        $detalhe = new \stdClass;
        if (isset($this->layout['detalhes'])) {
            foreach ($this->layout['detalhes'] as $segmento => $segmentoDefinitions) {
                // pula segmentos informados como "exceto" no parametro da função
                if (in_array($segmento, $excetoSegmentos)) {
                    continue;
                }
                $detalhe->$segmento = new \stdClass;
                foreach ($segmentoDefinitions as $field => $definition) {
                    $detalhe->$segmento->$field = (isset($definition['default'])) ? $definition['default'] : '';
                }
            }
        }
        return $detalhe;
    }

    public function inserirDetalhe(\stdClass $detalhe)
    {
        $this->detalhes[] = $detalhe;
        return $this;
    }

    public function countDetalhes()
    {
        return count($this->detalhes);
    }

    public function limpaDetalhes()
    {
        $this->detalhes = array();
        return $this;
    }

    public function jsonSerialize()
    {
        $headerLote = $this->header->jsonSerialize();
        $trailerLote = $this->trailer->jsonSerialize();
        $detalhes = $this->detalhes;

        return array_merge(
            array('codigo_lote' => $this->sequencial),
            array('header_lote' => $headerLote),
            array('detalhes' => $detalhes),
            array('trailer_lote' => $trailerLote)
        );
    }
}
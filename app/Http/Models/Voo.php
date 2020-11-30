<?php

namespace App\Http\Models;
use Illuminate\Database\Eloquent\Model;
use stdClass;
use Hash;

class Voo extends Model
{
    /**
     * Ordena os voos, agrupa aqueles que forem da mesma tarifa
     * e agrupa os que forem do mesmo preço final
     *  
     * @param collect
     * @return collect
     */
    public static function ordenaVoos($voos) {

        $voos = self::adicionarSentido($voos);

        //Agrupa os voos pelo sentido de ida e volta e coloca os preços como id para melhor verificação depois
        $return = $voos->groupBy([
            'sentido',
            function ($item) {
                return $item->preco;
            },
        ]);

        $Idas = self::agrupaVoosIdaVolta(collect($return['Ida']));
        $Voltas = self::agrupaVoosIdaVolta(collect($return['Volta']));
        $return = self::agrupaTodosOsVoos($Idas,$Voltas);

        return $return;

    }

    /**
     * Recebe os voos e adiciona o sentido ida e volta pela variavel outbound
     * 
     * @param collect
     * @return collect
     */
    private static function adicionarSentido($voos) {

        $retorno = collect([]);

        $voos->each(function ($item, $key) use ($retorno) {

            $aux = new stdClass();
            $aux->id = $item->id;
            $aux->tarifa = $item->fare;
            $aux->preco = $item->price;
            $aux->sentido = $item->outbound == 1 ? 'Ida' : 'Volta';
            $retorno->push($aux);

        });

        return $retorno;

    }

    /**
     * Recebe os voos e os agrupa em dois arrays de ida e volta
     * 
     * @param collect
     * @return collect
     */
    private static function agrupaVoosIdaVolta($Voos) {

        $r = collect([]);
        $Voos->each(function ($item, $key) use ($r) {
            $retorno_voos = [];
            $aux = new stdClass();
            foreach($item as $item_) {
                array_push($retorno_voos,"Voo ".$item_->id);
                $aux->id = $item_->id;
                $aux->tarifa = $item_->tarifa;
                $aux->preco = $item_->preco;
                $aux->sentido = $item_->sentido;
            }
            $aux->voos = $retorno_voos;
            $r->push($aux);
        });

        return $r;

    }

    /**
     * Agrupa os voos de ida e de volta pelas suas tarifas
     * Agrupa os voos que possuem o mesmo preço final
     * 
     * @param collect
     * @return collect
     */
    public static function agrupaTodosOsVoos($Idas,$Voltas) {

        $return = collect([]);
        $mesmoPreco = false;
        
        foreach($Idas as $_Idas) {
            foreach($Voltas as $_Voltas) {
                if ($_Idas->tarifa == $_Voltas->tarifa) {
                    $i = 0;
                    $aux_array_inbound  = [];
                    $aux_array_outbound = [];
                    foreach($return as $_return) {
                        if ($_return->totalPrice == ($_Idas->preco + $_Voltas->preco)) {
                            $mesmoPreco = true;
                            foreach($_return->outbound as $item) {
                                array_push($aux_array_inbound, $item);
                            }
                            foreach($_return->inbound as $item) { 
                                array_push($aux_array_outbound, $item);
                            }
                            break;
                        }
                        $i++;
                    }
                    $aux = new stdClass();
                    $aux->outbound = [];
                    $aux->inbound = [];
                    if ($mesmoPreco) {
                        foreach($aux_array_outbound as $item) {
                            array_push($aux->outbound, $item);
                        }
                        foreach($aux_array_inbound as $item) { 
                            array_push($aux->inbound, $item);
                        }
                        foreach($_Idas->voos as $item) {
                            array_push($aux->outbound, $item);
                        }
                        foreach($_Voltas->voos as $item) { 
                            array_push($aux->inbound, $item);
                        }
                        $_return->outbound = $aux->outbound;
                        $_return->inbound = $aux->inbound;
                        $mesmoPreco = false;
                    } else {
                        $aux->uniqueId = app('hash')->make(rand(0,5));
                        foreach($_Idas->voos as $item) { 
                            array_push($aux->outbound, $item);
                        }
                        foreach($_Voltas->voos as $item) { 
                            array_push($aux->inbound, $item);
                        }
                        $aux->totalPrice = $_Idas->preco;
                        $aux->totalPrice += $_Voltas->preco;
                        $return->push($aux);
                    }
                }
            }
        }

        return $return;

    }

}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Log;
use stdClass;
use App\Http\Models\Voo;
use Laravel\Lumen\Routing\Controller as BaseController;

class VooController extends Controller
{

    public function index()
    {
        $client = new Client(); //GuzzleHttp\Client
        $return = new stdClass(); //Retorno do get
        try {

            //Pega o json da API externa
            $result = $client->request('GET', 'http://prova.123milhas.net/api/flights');

            //Salva todos os voos possiveis
            $return->flights = json_decode($result->getBody()->getContents(),true);
            //Cria os grupos de voos possiveis
            $return->groups = Voo::ordenaVoos(collect(json_decode($result->getBody())));
            //Conta a quantidade de grupos
            $return->totalgroups = $return->groups->count();
            //Conta a quantidade de voos
            $return->totalFlights = collect(json_decode($result->getBody()))->count();
            //Salva o menor voo possivel
            $return->cheapestPrice = $return->groups->min('totalPrice');
            //Salva o id do menor voo possivel
            $return->cheapestGroup = $return->groups->where('totalPrice', 200)[0]->uniqueId;
            
            return response()->json($return);
        } catch (GuzzleException $e) {
            Log::error($e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 500,
                    'message' => 'Fonte de dados indisponivel.',
                ]
            ], 500);
        }

        return response()->json(['msg' => 'index']);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistroDeFabricacion;
use App\Models\OrdenProduccion;
use App\Models\FamiliaComercial;
use App\Models\CategoriaDeGarantia;
use App\Models\Fam_COM_Garantia;


use DB;
use GuzzleHttp\Client;
class RegistroDeFabricacionController extends Controller
{
    private  $user = "PS1_ADMIN";
    private  $pass = "Piero2810!!!";
	

   public function datosRegFabricacion($orden, $etiqueta,$empresa){
       $registroDeFabricacion = RegistroDeFabricacion::where('nr-ord-produ',$orden)->where('nr-etiq',$etiqueta)->where('empresa',$empresa)->first();

        if($registroDeFabricacion != null) return $registroDeFabricacion;
        return null;
    }
    public function ordenDeProduccion($numeroDeOrden,$empresa){
        $ordendeproduccion = OrdenProduccion::where( "nr-ord-produ",$numeroDeOrden)->where('empresa',$empresa)->first();

        if($ordendeproduccion != null) return $ordendeproduccion;
        return null;
        
    }
    public function datosRegFabricacionCompletos(Request $request){
        
        $etiquetavieja = $this->checkEtiqueta($request->etiqueta);
        if($etiquetavieja){
                
            $arreglo = strtoupper($request->etiqueta);
            $arreglo = explode("E", $arreglo);
            $numeroDeOrden = $arreglo[0];
            $numeroEtiqueta = $arreglo[1];

        $registro = $this->datosRegFabricacion($numeroDeOrden,$numeroEtiqueta,$request->empresa);
        $orden =  $this->ordenDeProduccion($numeroDeOrden,$request->empresa);

       

        if($registro == null){
            // return response()->json($registro);
            $result   = new \stdClass();

            $result->error = 1;
            $result->error_msg = "Orden o etiqueta incorrectos!";

            

            return response()->json($result);
            
        }
        if($orden!= null ) {
            // $ordencompleta = new \stdClass();
            // $ordencompleta->registro = $registro;
            $registro->success = 1;
            $registro->sap =  0;

            $registro->itcodigo = $orden->{'it-codigo'} ?? "" ;
            $registro->orden = $orden->{'nr-ord-produ'} ?? "" ;
            $registro->etiqueta = (int)$registro->{'nr-etiq'} ?? "" ;
            // if($registro->etiqueta != "") $registro->etiqueta = parseInt($registro->etiqueta);
            $registro->descripcion =  $this->getDescripcionItem($orden->{'it-codigo'},$request->empresa);
            // $registro->tipoGarantia->cat   = $this->getTipoGarantiaItem($registro->{'it-codigo'});
			$registro->regFabricacion = new \stdClass();

            $registro->regFabricacion->tipoGarantia   = new \stdClass();

            $cat = $this->getTipoGarantiaItem($registro->itcodigo,$request->empresa);
            $registro->regFabricacion->tipoGarantia->cat   = $cat;
        
            // $registro->regFabricacion->tipoGarantia->cat   = 2;
            $registro->regFabricacion->tipoGarantia->lapsoValidez   = 2;
            return response()->json($registro);
        }

         }
         else{
             
            
            $etiqueta  =  str_pad($request->etiqueta, 20, "0", STR_PAD_LEFT);
            $registro = $this->getRegistroSap($etiqueta);
             $registro = $registro->original;

            

             if(isset($registro->error))  {

                $registro = new \stdClass();
                $registro->error = true;
                $registro->error_msg ="No se encontro ningun registro de fabricacion.";
                 
                 return response()->json($registro);
                 

               }
               else{

                
                    $registro->sap =  1;
                    $registro->success = 1;
                    $registro->regFabricacion = new \stdClass();
                    $registro->regFabricacion->tipoGarantia   = new \stdClass();
                        
                    $registro->itcodigo = $registro->Matnr ;
                   
                    $registro->regFabricacion->tipoGarantia   = new \stdClass();

                    $cat =$this->getTipoGarantiaItemSAP($registro->itcodigo,$request->empresa);
             
                    $registro->regFabricacion->tipoGarantia->cat  = $cat;
                    $registro->orden =  $registro->Charg;
                   
                    $registro->descripcion = $this->getDescripcionItemSap($registro->itcodigo)->descripcion;
                    $registro->etiqueta = "";
                    
    
                 return response()->json($registro);
               }  
               

         }
      
    

        return response()->json($registro);

    }
    //resuelve si la etiqueta es una UA de sap o un codigo viejo en sql
    public function checkEtiqueta($codigo){
        if(str_contains($codigo,'E') || str_contains($codigo,'e') ) return true;
        else return false;
    }
    public function getDescripcionItem($itCodigo,$empresa) {

        // $itCodigo = $request->itCodigo;
        $result = DB::connection('sqlsrv')->table('item')->where('it-codigo', $itCodigo)->where('empresa',$empresa)->first();
            
        return $result->{'desc-item'};

    }
    public function getDescripcionItemSap($codigo){
        $client = new \GuzzleHttp\Client();
        $codigo = "0000".$codigo;
        try{

        $response = $client->get('https://vhpirps1ci.hec.grupopiero.com:44300/sap/opu/odata/sap/ZGW_STOCKECO_INFO_SRV/ProductoDescSet(%27'.$codigo.'%27)?$format=json', [    'auth' => [$this->user, $this->pass] ,
         'headers' => [
             'Accept' => 'application/json'
             ]  ]);
            
        }
        catch(\Exception $e){
                $result = new \stdClass();
                $result->success= false;
                $result = "no encontrado";
            return response()->json($result);
        }
        $response = $response->getBody()->getContents();
        $response = json_decode($response)->d->Maktx;
        $result = new \stdClass();
        $result->success= true;
        $result->descripcion = $response;

    

        return $result;

    }

    public function item(Request $request) {

        $itcodigo = $request->itcodigo;
        $etiqueta = $request->etiqueta;
        $empresa = $request->empresa;
        //seguir aca
        if($etiqueta >= 0 ){
            
            $result = DB::connection('sqlsrv')->table('item')->where('it-codigo', $itcodigo)->where('empresa',$empresa)->first();
            if($result != null){
                $result->success= true;
                $result->descripcion  = $result->{'desc-item'};
                return response()->json($result);

            }
        }
            else{
            //    $descripcionSAP =  $this->getDescripcionItemSap($itcodigo);
               $descripcionSAP =  $this->getDescripcionItemSap($itcodigo);
            //    $result = new \stdClass();
            //    $result->success= true;
            //    $result->descripcion= $descripcionSAP->descripcion;
      
               return response()->json($descripcionSAP);

            }
        return response()->json($result);

    }
        /**
     * Get Movimientos con saldo de cliente para cuenta corriente
     */
    public function getMovimientosConSaldo(Request $request) {
        $cliente = $request->cliente;
        $result = DB::connection('sqlsrv')->table('titulo')->where('cod-emitente', $cliente)->where('vl-saldo','!=' ,0)->get();

        return response()->json($result);
 
    }
        /**
     * Get TIPO garantia item
     */
    public function getTipoGarantiaItemAPI(Request $request) {
        $itCodigo = $request->itCodigo;
        $result = DB::connection('sqlsrv')->table('item')->where('it-codigo', $itCodigo)->first();
        
        if($result != null){
            $result1 = DB::connection('sqlsrv')->table('fam_com_garantias')->where('fam_com', $result->{'fm-cod-com'})->where('empresa',$request->empresa)->first();
            return $result1;
        }
        return response()->json(false);
        //agregar try catch

    }
    public function getTipoGarantiaItemSAP($itcodigo,$empresa) {
        // $itCodigo = $request->itCodigo;
        // $result = DB::connection('sqlsrv')->table('item')->where('it-codigo', $itCodigo)->first();
        $familia = $this->getFamiliadeItemSAP($itcodigo);
        if($familia == null) return "";
        else{
            $result1 = DB::connection('sqlsrv')->table('fam_com_garantias')->where('fam_com', $familia)->where('empresa',$empresa)->first();
            return $result1 ? $result1->cat_garantia : false;
        }
        
     
        //agregar try catch

    }
    public function getFamiliadeItemSAP($itcodigo){
        $itcodigo = "0000".$itcodigo;
        $client = new \GuzzleHttp\Client();
        try{

        $response = $client->get('https://vhpirps1ci.hec.grupopiero.com:44300/sap/opu/odata/sap/ZGW_STOCKECO_INFO_SRV/RelGrupoMaterialSet(%27'.$itcodigo.'%27)?$format=json', [    'auth' => [$this->user, $this->pass] ,
         'headers' => [
             'Accept' => 'application/json'
             ]  ]);
            
        }
        catch(\Exception $e){
                $result = new \stdClass();
                $result->success= false;
            return response()->json($result);
        }
    
        $response = $response->getBody()->getContents();
        $response = json_decode($response);
        if(isset($response->d)){
            return $response->d->Mvgr2;

        }
        else{
            return null;
        }

    
    }
    public function getEtiquetaSAP(){
        ini_set('memory_limit', '500M');

        $client = new \GuzzleHttp\Client();
        try{

        $response = $client->get('https://vhpirps1ci.hec.grupopiero.com:44300/sap/opu/odata/sap/ZGW_STOCKECO_INFO_SRV/RegFabricacionSet?$format=json', [    'auth' => [$this->user, $this->pass] ,
         'headers' => [
             'Accept' => 'application/json'
             ]  ]);
            
        }
        catch(\Exception $e){

            return response()->json($e,500);
        }
    
        $response = $response->getBody()->getContents();
        $response = json_decode($response)->d->results;


        $coleccionItems = collect($response);
        // $coleccionItems->whereIn('Charg',"2203280940");
        $filtered = $coleccionItems->filter(function($item) {

          
            return $item->Charg == "2203280940";
        })->values()->all();

        return response()->json($filtered);

        
    }
    public function  getRegistroSap($codigo){
        $client = new \GuzzleHttp\Client();
        try{

        $response = $client->get('https://vhpirps1ci.hec.grupopiero.com:44300/sap/opu/odata/sap/ZGW_STOCKECO_INFO_SRV/RegFabricacionSet(%27'.$codigo.'%27)?$format=json', [    'auth' => [$this->user, $this->pass] ,
         'headers' => [
             'Accept' => 'application/json'
             ]  ]);
            
        }
        catch(\Exception $e){
                $result = new \stdClass();
                $result->success= false;
                $result->error= false;

            return response()->json($result);
        }
    
        $response = $response->getBody()->getContents();
        $response = json_decode($response);
        if(isset($response->d)){
            return response()->json($response->d);

        }


         $result = new \stdClass();
                $result->success= false;
                $result->error= false;

        return response()->json($response);


    }
    // funcion que obtiene el nombre de la familia comercial dado un id 
    // Ejemplo 001 devuelve Box platino
    public function getDescripcionFamiliaSAP($itcodigo){
        $client = new \GuzzleHttp\Client();
        try{

        $response = $client->get('https://vhpirps1ci.hec.grupopiero.com:44300/sap/opu/odata/sap/ZGW_STOCKECO_INFO_SRV/GrupoMaterialSet(%27'.$itcodigo.'%27)?$format=json', [    'auth' => [$this->user, $this->pass] ,
         'headers' => [
             'Accept' => 'application/json'
             ]  ]);
            
        }
        catch(\Exception $e){
                $result = new \stdClass();
                $result->success= false;
            return response()->json($result);
        }
    
        $response = $response->getBody()->getContents();
        $response = json_decode($response)->d->Bezei;


    

        return $response;


    }
    public function  getGruposMateriales(){
        $client = new \GuzzleHttp\Client();
        try{

        $response = $client->get('https://vhpirps1ci.hec.grupopiero.com:44300/sap/opu/odata/sap/ZGW_STOCKECO_INFO_SRV/GrupoMaterialSet?$format=json', [    'auth' => [$this->user, $this->pass] ,
         'headers' => [
             'Accept' => 'application/json'
             ]  ]);
            
        }
        catch(\Exception $e){
            return response()->json($e,500);
        }
    
        $response = $response->getBody()->getContents();
        $response = json_decode($response)->d->results;


    

        return response()->json($response);


    }

    public function getTipoGarantiaItem($itCodigo,$empresa){
        $result = DB::connection('sqlsrv')->table('item')->where('it-codigo', $itCodigo)->where('empresa',$empresa)->first();
        
        if($result != null){
            $result1 = DB::connection('sqlsrv')->table('fam_com_garantias')->where('fam_com', $result->{'fm-cod-com'})->where('empresa',$empresa)->first();
            return $result1 ? $result1->cat_garantia : false;
        }
        return "not found";
    }

        /**
     * Get items con alguna garantia ya sea legal o GEP
     */
    public function getItems() {
   

        $result = DB::connection('sqlsrv')->table('FAM_COM_GARANTIAS')->get();

    
        foreach ($result as $row) {
        $descFam = DB::connection('sqlsrv')->table('fam-comerc')->where('fm-cod-com',$row->fam_com)->first();
        $descripcion =  $descFam == null ? "" : $descFam->descricao;

            $familiasEnGarantia[] = array("categoriaGarantia" => utf8_encode($row->cat_garantia), "famComercial" => utf8_encode($row->fam_com), "descFamComercial" => utf8_encode($descripcion));
        }

        return $familiasEnGarantia;
    }

        /**
     * Get INFO CLIENTE from CUIT
     */
    public function getInfoCliente(Request $request) {

        $cuit = $request->cuit;
        $result = DB::connection('sqlsrv')->table('emitente')->where('cgc',$cuit)->where('identific',1)->first();
        if($result != null){
            $result->success = 1;
            $result->cliente =  new \stdClass();
            $result->cliente->nombre = $result->{'nome-emit'};
            $result->cliente->codEmitente = $result->{'cod-emitente'};
        }
        else{
            $result =  new \stdClass();

            $result->error = 1;
            $result->error_msg = "Cliente inexistente!";
        }
        
        return response()->json($result);
    }

        /**
     * Get user by email and password
     */
    public function getUserByEmailAndPassword(Request $request) {

        $id= $request->id;
        $password = $request->password;

        $result = DB::connection('sqlsrv')->table('perfil')->where('CLI_ID',$id)->first();
        
        if (is_array($result))  {

            if ($result['CLI_PASSWORD'] == $password) {
                // user authentication details are correct
                return $result;
            }else{
                return false;
            }
        } else {
            // user not found
            return false;
        }
    }

    public function getFamiliasComerciales(){
        $familiascomerciales = FamiliaComercial::all();
        return response()->json($familiascomerciales);

    }
    public function getCategoriasDeGarantias(){
        $categoriasDeGarantias = CategoriaDeGarantia::where('empresa',request('empresa'))->get();
        return response()->json($categoriasDeGarantias);

    }
    public function getFamComGarantias(Request $request){
        $famComGarantias = Fam_COM_Garantia::with('categoria')->with('familiaComercial')->where('empresa',$request->empresa)->get();
        foreach ($famComGarantias as $key => $fam) {
            if(strlen($fam->fam_com) <= 4){
               $fam->descripcion = $this->getDescripcionFamiliaSAP($fam->fam_com);
                
            }

        }
        
        return response()->json($famComGarantias);
        
    }
    public function addFamComGarantia(Request $request){
        try{
        $famComGarantias = new Fam_COM_Garantia();
        $famComGarantias->fam_com = $request->familiaComercial;
        $famComGarantias->cat_garantia = $request->categoria ;
        $famComGarantias->empresa = $request->empresa ;
        $famComGarantias->save();    
            
        }
        catch(\Exception $e){
         return response()->json($e);
        }

        return response()->json($famComGarantias);
    }
    public function deleteFamComGarantia(Request $request){
        
        try{
            $famComGarantias =  Fam_COM_Garantia::where('fam_com',$request->valoraborrar)->where('empresa',$request->empresa)->delete();
            
            return response()->json("se elimino con exito el registro");

        }
        catch(\Exception $e){
            return response()->json($e);
           }
        return response()->json("se elimino con exito el registro");

        }

    function callAPI($method, $url, $data){

        $client = new GuzzleHttp\Client(['base_uri' => 'https://foo.com/api/']);
        // Send a request to https://foo.com/api/test
        $response = $client->request('GET', 'test');
        // Send a request to https://foo.com/root
        $response = $client->request('GET', '/root');


        $curl = curl_init();

        switch ($method){
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);			 					
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    // OPTIONS:
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'APIKEY: 111111111111111111111',
        'Content-Type: application/json',
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

    // EXECUTE:
    $result = curl_exec($curl);
    if(!$result){die("Connection Failure");}
    curl_close($curl);
    return $result;
}
}

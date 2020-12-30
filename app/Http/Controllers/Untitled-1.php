<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use GuzzleHttp\Client;

use App\Garantia;

use App\CategoriaGarantia;

use App\Evento;

use QrCode;

use Flash;

use Auth;

use Mail;
use File;
use Carbon\Carbon;

use App\Http\Requests\GarantiaRequest;

class GarantiasController extends Controller
{
	private $credentials ;

	public function __construct()
	{
	  $this->credentials = base64_encode('augusto:augusto');
	}
	public function index()
    {	
    	$garantias = Garantia::orderBy('id', 'ASC')->paginate(5);
		
		return view('admin.garantias.index')->with('garantias', $garantias);
    }

	public function getGarantiasUsr($id)
	    {

	    	$garantias = Garantia::orderBy('id', 'ASC')->where('user_id', '=', $id )->get();
				
			$garantias = $this->completaDatosParaMostrarGarantias($garantias);
			return view('garantias.lista')->with('garantias', $garantias);

	    }

		public function productosgep()
	    {
				$client = new Client(['base_uri' => 'https://clientes.piero.com.ar', [
            'headers' => [
                'Authorization' => 'Basic ' . $this->credentials,
            ]]]);
				$response = $client->request('GET', '/modulos/webService', [
			    	'query' => [
			    	    'tag' => 'garantiasGEP',
			    	]
					]);
				$jsond = json_decode($response->getBody());
	    	return view('garantias.productosgep')->with('productosgep', $jsond);

	    }

		public function confidencialidadypdp()
		  {
		    return view('garantias.confidencialidadypdp');
		  }

		public function tyc()
			 {
			    return view('garantias.tyc');
			 }

    public function getIndex()
    {
		
    	if(Auth::guest()){
    		return view('welcome');
    	}
  		else{
			$garantias = Garantia::where('user_id', '=', Auth::user()->id )->paginate(10);
				$garantias = $this->completaDatosParaMostrarGarantias($garantias);
    		return view('admin.users.piero')->with('garantias', $garantias);
			}
    }

		public function completaDatosParaMostrarGarantias($garantias){
			$empresa = 1;
			foreach ($garantias as $key => $garantia) {
				//LLAMO A LA API PARA DESCRIPCION DE ITEM
			// $client = new Client(['base_uri' => 'http://10.0.4.30/api-garantias/public/api/item', [
            // 'headers' => [
            //     'Authorization' => 'Basic ' . $this->credentials,
            // ]]]);
			// $response = $client->request('GET', '', [
			// 		'query' => [
			// 				'tag' => 'item',
			// 				'itcodigo' => $garantia->it_codigo,
			// 		]
			// 	]);
			$client = new Client();
			$response = $client->get('http://10.0.4.30/api-garantias/public/api/item?itcodigo='.$garantia->it_codigo.'&empresa='.$empresa.'&etiqueta='.$garantia->etiqueta,
        [
            'headers' => [
                'Authorization' => 'Basic ' . $this->credentials,
            ],
        ]);

				$jsond = json_decode($response->getBody());
			if($jsond->success){
				
				$garantia->desc = $jsond->descripcion;

								$garantia->caducidad =  $this->calculoCaducidadGarantia($garantia);
								$garantia->porcentajeReconocido = $this->calculoPorcentajeReconocido($garantia->id_garantia);

								if(!$garantia->ejecutada){ //    4/6/18 se muestran ejecutadas tambien - 28/9/17 solo se muestran NO ejecutadas
										if(strtotime(date("Y-m-d", strtotime(str_replace('/', '-', $garantia->caducidad)))) < strtotime(date('Y-m-d'))) {
												$garantia->disabled = 'disabled';
												$garantia->style = 'label-danger';
										}else{
												$garantia->style = 'label-success';
										}
								}else{
										$garantia->disabled = 'disabled';
										$garantia->style = 'label-info';
								}
			}

			}

			return $garantias;
		}

    public function calculoCaducidadGarantia($garantia){
			$categoria = CategoriaGarantia::find($garantia->id_categoria)->first();
			if($garantia->sustituye_orden == 0){ //dependiendo si es un colchon de recambio o no toma como fecha de recepcion la original o la del colchon ingresado
				return date ( 'd/m/Y' , strtotime('+'.$categoria->lapso_meses.' month' , strtotime(date($garantia->fecha_recepcion))));
			}else{
				return date ( 'd/m/Y' , strtotime('+'.$categoria->lapso_meses.' month' , strtotime(date($garantia->fecha_recepcion_orig))));
			}
		}

    private function calculoPorcentajeReconocido($id){
        $garantia = Garantia::where('id_garantia', '=', $id)->first();
        $categoria = CategoriaGarantia::find($garantia->id_categoria);

				if($garantia->sustituye_orden == 0){
					$garantia->caducidad =  date ( 'd/m/Y' , strtotime('+'.$categoria->lapso_meses.' month' , strtotime(date($garantia->fecha_recepcion))));
	        $caducidad = \DateTime::createFromFormat('d/m/Y', $garantia->caducidad);
	        $hoy = new \DateTime('now');
	        $fecha_recepcion = \DateTime::createFromFormat('Y-m-d', $garantia->fecha_recepcion);
				}else{
					$garantia->caducidad =  date ( 'd/m/Y' , strtotime('+'.$categoria->lapso_meses.' month' , strtotime(date($garantia->fecha_recepcion_orig))));
	        $caducidad = \DateTime::createFromFormat('d/m/Y', $garantia->caducidad);
	        $hoy = new \DateTime('now');
	        $fecha_recepcion = \DateTime::createFromFormat('Y-m-d', $garantia->fecha_recepcion_orig);
				}


        $diferencia = $fecha_recepcion->diff($hoy);
        $intervalMeses=$diferencia->m;
        $intervalAnos = $diferencia->y*12;
        $meses = $intervalMeses+$intervalAnos;
	
        if($garantia->id_categoria == 2 || $garantia->id_categoria == 3){

					if($meses > 60){
						return "100%";
					}else if($meses <= 60 && $meses > 48){
						return "90%";
					}else if($meses <= 48 && $meses > 36){
						return "75%";
					}else if($meses <= 36 && $meses > 24){
						return "50%";
					}else if($meses <= 24 && $meses > 12){
						return "25%";
					}else if($meses <= 12 && $meses > 6){
						return "0%";
					}else{
						return "0%";
					}

        }else{
            return "Consultar (4117-7200)";
        }
    }

		public function sustituido(){
			$orodenVieja = $this->checkOrden( $_GET['ord']);
			$orden= $_GET['ord'];
			$etiqueta = 0;
			if($orodenVieja){
				$arreglo = strtoupper($orden);
				$arreglo = explode("E", $arreglo);
				$orden = $arreglo[0];
				$etiqueta = $arreglo[1];
			}   
			else{
				
				$orden  =  str_pad($orden, 20, "0", STR_PAD_LEFT);
				$etiqueta = -1;
		
			}
			$garantia = Garantia::where('orden' , '=', $orden)->where('etiqueta' , '=', $etiqueta)->first();
    		if(!$garantia){
    			$garantia = null;
    			return '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> Los datos ingresados no corresponden con ninguno de los registrados. Intente nuevamente.</div>';
    		}else{

					if($garantia->fecha_ejecucion != "0000-00-00"){
					$fechaCompraFormat = \DateTime::createFromFormat('Y-m-d', $garantia->fecha_compra);
					$fechaCompra = $fechaCompraFormat->format('d/m/Y');
/*
					if($garantia->sustituye_orden != 0){ //si orden es distinta a cero quiere decir que la garantia proviene de un reemplazo anterior por lo que la fecha de recepcion no sera la de ese producto sino la original
							$fechaRecepcion = $garantia->fecha_recepcion_orig;
					}else{
							$fechaRecepcion = $garantia->fecha_recepcion;
					}
*/
					return '	<div class="row">
		<div class="col-md-12">

	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><i class="fa fa-info-circle"></i> Detalles producto sustituido <input class="form-control" name="recepcionOriginal" id="recepcionOriginal" type="hidden" value="'.$garantia->fecha_recepcion_orig.'" ></h3>
			<span class="pull-right clickable"><i class="glyphicon glyphicon-chevron-up"></i></span>
		</div>
		<div class="panel-body">
				<div class="col-sm-6">
				<div style="padding-right:5px;">
		<div class="form-group">
				<label class="control-label" for="email">Factura: </label>
				<input type="text" name="numFactura" id="numFactura" class="form-control" value="'.$garantia->factura.'" placeholder="Numero de factura prod. Adquirido" readonly=readonly>
		</div>
		<div class="form-group">
				<label class="control-label" for="email">Adquirido a: </label>
				<input type="text" class="form-control" id="cuitEntidad" name="cuitEntidad" value="'.$garantia->cuit_adquirido.'" placeholder="C.U.I.T del comercio donde adquirió producto" readonly=readonly>
		</div>
		<div class="form-group">
				<label class="control-label" for="email">Fecha Recepción producto: </label>
				<input class="form-control" name="recepcionProd" id="recepcionProd" type="text" value="'.$garantia->fecha_recepcion.'" readonly=readonly>
		</div>
		</div>
		</div>
				<div class="col-sm-6">
				<div style="padding-left:5px;">
		<div class="form-group">
				<label class="control-label" for="email">Fecha compra: </label>
				<input class="form-control" name="fechaCarton" id="fechaCarton" type="text" value="'.$fechaCompra.'" readonly=readonly>
		</div>
		<div class="form-group">
				<label class="control-label" for="email">Razon social / Local Piero: </label>
				<input type="text" name="razonSoc" class="form-control" value="'.$garantia->adquirido_a.'" placeholder="Ingrese razon social presente en factura de compra" readonly=readonly>
		</div>
		<div class="form-group">
				<label class="control-label" for="email">Fecha Ejecución Garantía: </label>
				<p id="ejecucionDate" style="margin-top:5px;">'.$garantia->fecha_ejecucion.'</p>
		</div>
		</div>
		</div> <!-- panel -->
		</div>
	</div>
	<div class="form-group" style="margin-left:10px;margin-right:10px;">
			<label class="control-label" for="email">Seleccione fecha recepción nuevo producto: <i style="color:#2c3e50;font-size:18px;vertical-align:middle;" class="fa fa-question-circle" data-container="body" data-trigger="hover" data-placement="bottom" data-toggle="popover" title="Ayuda" data-content="Seleccione presionando sobre el siguiente campo la fecha de recepcion del producto que esta registrando."></i> </label>
			<input class="form-control" name="fechaRecepcion" id="fechaRecepcion" type="text" style="background:white;cursor:pointer;" value="'.$garantia->fecha_ejecucion.'" readonly=readonly>
	</div>
						<button style="line-height: 3;width: 100%;min-width: 140px;" id="regCompra" class="btn btn-primary" type="button" data-toggle="modal" data-target="#terminos">Registrar compra</button>

		</div> <!-- .col-md-12 -->

	</div> <!-- .row -->';

				}
				else{
						return '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i>  <strong>El producto especificado no posee aún una garantía ejecutada.</strong>  </br>Ingrese el registro de fabricación correctamente o contactese con atención al cliente. </br> ( 4117-7200 o atencionalcliente@piero.com.ar )</div>';
				}
				}
		}
		public function imagesUploadPost(Request $request)
		{
				try {
					$this->validate($request, [
					'selectProducto' => 'required',
					'uploadFile' => 'required',
				]);
	
					$path = public_path('img/garantiasDocs')."/".$request->input('selectProducto');
					if(!File::exists($path)) {
						// if(File::exists($path)) {
						File::makeDirectory($path, 0775, true);
					}
					else{
						/* Aviso que hay reclamo en curso o ELIMINO Y SOBREESCRIBO - decidir */
						Flash::warning('<i style="font-size:24px;vertical-align: middle;" class="fa fa-exclamation-triangle" aria-hidden="true"></i> <span style="vertical-align:middle;">Ya existe un reclamo en curso para esta garantía. Contactese a '.env("SERVICIO_ATENCION_CLIENTE_EMAIL", "webmaster@grupopiero.com").'</span>');
						return redirect()->action('GarantiasController@getIndex');
					}
					// dd($request->file('uploadFile'));
					if(($request->hasfile('uploadFile')))
					foreach ($request->file('uploadFile') as $key => $value) {
							$imageName = $request->input('selectProducto')."-". $key . '.' . $value->getClientOriginalExtension();
							$value->move($path, $imageName);
					}
	
				$idGenerado = ['idGarantia' => $request->input('selectProducto'), 'user' => Auth::user()->email];
					Mail::send('emails.envioDocumentacion', $idGenerado, function ($message) use ($request, $path){
				  $message->subject('Solicitud de Garantía para revisión');
				//   $message->to("nfortes@grupopiero.com"); 
				  $message->to(env("SERVICIO_ATENCION_CLIENTE_EMAIL", "garantias@piero.com")); 
					// webmaster@grupopiero.com
					if(($request->hasfile('uploadFile')))
				  foreach ($request->file('uploadFile') as $key => $value) {
					  $imageName = $request->input('selectProducto')."-". $key . '.' . $value->getClientOriginalExtension();
					  $message->attach($path."/".$imageName);
				  }
				});
	
					Flash::success('<i style="font-size:24px;vertical-align: middle;" class="fa fa-check" aria-hidden="true"></i> <span style="vertical-align:middle;">La informacion se ha enviado exitosamente.</span>');
					return redirect()->action('GarantiasController@getIndex');
				} catch (Exception $e) {
					Flash::error('<i style="font-size:24px;vertical-align: middle;" class="fa fa-times" aria-hidden="true"></i> <span style="vertical-align:middle;">La informacion no ha podido enviarse. intente nuevamente o contactese a atencionalcliente@'.env('BASE_NOMBRE_EMP_MIN').'com.ar</span>');
					return redirect()->action('GarantiasController@getIndex');
				}
		}
	public function Consulta($id = null)
    {
		$empresa = 1;
		
    	if(\Request::get('txt_garantias')){
    		$id = \Request::get('txt_garantias');
    		$urlQr = "qrcodes/";
    	}else{
    		$urlQr = "../../qrcodes/";
    	}


    	if($id != null){
		$garantia = Garantia::where('id_garantia' , '=', $id)->first();
		// var_dump($garantia);

    		if(!$garantia){
    			$garantia = null;
    			Flash::error("ID de garantia inexistente!");
    		}else{
    		/* Traigo relaciones */
    			$garantia->user;
			/* Busco en API Descripcion */
			

			$client = new Client();
			$response = $client->get('http://10.0.4.30/api-garantias/public/api/item?itcodigo='.$garantia->it_codigo.'&empresa='.$empresa.'&etiqueta='.$garantia->etiqueta,
					[
						'headers' => [
							'Authorization' => 'Basic ' . $this->credentials,
						],
					]);


				// $client = new Client(['base_uri' => 'http://10.0.4.30/api-garantias/public/api/item', [
				// 'headers' => [
				//     'Authorization' => 'Basic ' . $this->credentials,
				// ]]]);
				
				// $response = $client->request('GET', '', [ 'query' => ['tag' => 'item', 'itcodigo' => $garantia->it_codigo, 	]]);
			$jsond = json_decode($response->getBody());

			if($jsond->success){
                //LLAMO A LA API PARA VERIFICAR QUE CLIENTE SEA DE PIERO
            //     $clientApi = new Client(['base_uri' => 'http://10.0.4.30/api-garantias/public/api/getInfoCliente', [
            // 'headers' => [
            //     'Authorization' => 'Basic ' . $this->credentials,
            // ]]]);
            //     $responseCli = $clientApi->request('GET', '', [
            //         'query' => [
            //             'tag' => 'cliente',
            //             'cuit' => $garantia->cuit_adquirido,
            //         ]
			// 	]);
				$clientApi = new Client(['base_uri' => 'https://api.grupopiero.com/api/getUserByCuit?cuit='.$garantia->cuit_adquirido.'','verify' => false]);
				$responseCli = $clientApi->request('GET', '');
				
				$jsonEsCliente = json_decode($responseCli->getBody());
				// var_dump($jsonEsCliente);

                $garantia->esClientePiero = $jsonEsCliente->success;
				$garantia->descripcion = $jsond->descripcion;
                $categoria = CategoriaGarantia::find($garantia->id_categoria);

								if($garantia->sustituye_orden == 0){ //chequeo que el item consultado no sea un reemplazo que deba continuar una garantia anterior
									$garantia->caducidad =  date ( 'd/m/Y' , strtotime('+'.$categoria->lapso_meses.' month' , strtotime(date($garantia->fecha_recepcion))));
								}else{
									$garantia->caducidad =  date ( 'd/m/Y' , strtotime('+'.$categoria->lapso_meses.' month' , strtotime(date($garantia->fecha_recepcion_orig))));
								}

                if(strtotime(str_replace('/', '-', $garantia->caducidad)) < strtotime(date('Y-m-d'))){
                    $garantia->disabled = 'disabled';
                    $garantia->style = 'label-danger';
                }else{
                    $garantia->style = 'label-success';
                }
				// var_dump($garantia);


			}else{
				$garantia->descripcion = "No encontrado";
			}
			// dd( $urlQr.$garantia->orden.$garantia->etiqueta.".png");
			$garantia->qr = $urlQr.$garantia->orden.$garantia->etiqueta.".png";
		}
		}else{
			$garantia = null;
		}
		$user = Auth::user();
		// dd($user);
		return view('garantias.consulta')->with('garantia', $garantia);
    }

    public function postGarantias(Request $request)
    {
    	$etiqueta = $request->input('etiqueta');
    	// Create a client with a base URI
		// $client = new Client(['base_uri' => 'http://10.0.4.30/api-garantias/public/api/datosRegFabricacionCompletos', [
        //     'headers' => [
        //         'Authorization' => 'Basic ' . $this->credentials,
		// 	]]]);
			
			$client = new Client();
			// $this->credentials = base64_encode('augusto:augusto');
			$response = $client->get('http://10.0.4.30/api-garantias/public/api/datosRegFabricacionCompletos?etiqueta='.$etiqueta.'&empresa=1',
					[
						'headers' => [
							'Authorization' => 'Basic ' . $this->credentials,
						],
					]);

		// $response = $client->request('GET', '', [
		// 	'query' => [
		     
		// 		'etiqueta' => $etiqueta,
		// 		'empresa' => 1
		//     ]
	
		// ]);
		// $json = json_encode($response->getBody());
		$json = $response->getBody();
		$jsond = json_decode($json);
		
		if(isset($jsond->success)){

				if($jsond->etiqueta=="") $jsond->etiqueta = "-1";
			$result = Garantia::where('orden', '=', $jsond->orden)->where('etiqueta', '=', $jsond->etiqueta)->first();
			
				$jsond->regFabricacion->qr = "qrcodes/".$jsond->orden.$jsond->etiqueta.".png";
			// dd($jsond);
			if ($result) {
				$jsond->regFabricacion->estado = '<p class="sub"><label>Garant&iacute;a: </label> <span style="font-size: 14px;" class="label label-danger">Registrado</span></p>';
			}else{
				$jsond->regFabricacion->estado = '<p class="sub"><label>Garant&iacute;a: </label> <span style="font-size: 14px;" class="label label-success">Sin Registrar</span></p>';
			}

			QrCode::format('png')->size(150)->margin(1)->generate($jsond->orden.$jsond->etiqueta, '../public/qrcodes/'.$jsond->orden.$jsond->etiqueta.'.png');

			$categoriaGarantia = CategoriaGarantia::find($jsond->regFabricacion->tipoGarantia->cat);
			if($categoriaGarantia!= null){

				$jsond->regFabricacion->tipoGarantia->lapsoValidez = $categoriaGarantia->lapso_meses;
			}
		
	
			
		return json_encode($jsond);
			
		}
		return json_encode($jsond);

	}
	

    public function postAdquirido(Request $request)
    {
    	$cuit = $request->input('cuitEntidad');

			//LLAMO A LA API PARA VERIFICAR QUE CLIENTE SEA DE PIERO
			$client = new Client(['base_uri' => 'https://api.grupopiero.com/api/getUserByCuit?cuit='.$cuit.'','verify' => false]);
			$response = $client->request('GET', '');
			$jsond = json_decode($response->getBody());
			if($jsond != null ) $jsond->success =1 ;
 			return json_encode($jsond);

    }

     /**
     * Inserta nueva garantia en BD
     */
    public function postNueva(GarantiaRequest $request)
    {
		$garantia = new Garantia($request -> all());
		$garantia->orden = $request->input('ordenProduccion');
		$garantia->etiqueta = $request->input('etiq') =="" ? -1 :$request->input('etiq') ;
        $garantia->it_codigo = $request->input('itemReg');
        $garantia->user_id = $request->input('userLogged');
        $garantia->cuit_adquirido = $request->input('cuitEntidad');
        $garantia->adquirido_a = $request->input('razonSoc');
        $garantia->factura = $request->input('numFactura');
        $garantia->id_categoria = $request->input('id_categoria');
				if($request->input('ordenSustituto') != ''){
					//aca va la logica  para ver el tipo de orden si es UA o etiqueta vieja.

					$orden= $request->input('ordenSustituto'); 
					$orodenVieja = $this->checkOrden($orden);
					$etiqueta = 0;
					if($orodenVieja){
						$arreglo = strtoupper($orden);
						$arreglo = explode("E", $arreglo);
						$orden = $arreglo[0];
						$etiqueta = $arreglo[1];
					}   
					else{
						
						$orden  =  str_pad($orden, 20, "0", STR_PAD_LEFT);
				
					}
					$garantia->sustituye_orden = $orden;
        			$garantia->sustituye_etiq = $etiqueta;
					if($request->input('recepcionOriginal') != 0){
						$garantia->fecha_recepcion_orig = date("Y-m-d", strtotime(str_replace('/', '-', $request->input('recepcionOriginal'))));
					}else{
						$garantia->fecha_recepcion_orig = date("Y-m-d", strtotime(str_replace('/', '-', $request->input('recepcionProd'))));
					}
				}

        $garantia->fecha_compra = date("Y-m-d", strtotime(str_replace('/', '-', $request->input('fechaCarton'))));
        $garantia->fecha_recepcion = date("Y-m-d", strtotime(str_replace('/', '-', $request->input('fechaRecepcion'))));
        $garantia->id_garantia = ( ( ( $garantia->user_id + $garantia->orden ) * $garantia->etiqueta ) * substr($garantia->cuit_adquirido, 0, 4) );
		// 68512  3
		$garantia->id_garantia = Carbon::now()->format('dmY').$garantia->id_garantia;
		$garantia->save();

        $idGenerado = ['idGarantia' => $garantia->id_garantia, 'user' => $garantia->user_id];
        Mail::send('emails.garantiaRegistrada', $idGenerado, function ($message){
    	  	$message->subject('Garantia Registrada');
	    	  $message->to(Auth::user()->email);
			 	});

        Flash::success('Su garantia ha sido registrada con exito. Recibirá un email en ' . Auth::user()->email . ' con un enlace al Registro GEP que podrá presentar en el local de compra ante una eventual falla de producto. 	&nbsp;<a target="_blank" href="pdf/' . $garantia->id_garantia . '" class="btn btn-primary"><i class="fa fa-download" aria-hidden="true"></i> Descargar Registro GEP</a>');
				return redirect()->action('GarantiasController@getIndex');

	}
	public function checkOrden($codigo){
        if(str_contains($codigo,'E') || str_contains($codigo,'e') ) return true;
        else return false;
    }

public function postLoginapi(Request $request){

		
        $id = $request->input('cliente');
        $pass = $request->input('password');
		

		try{

			//LLAMO A LA API PARA DESCRIPCION DE ITEM
			$client = new Client(['base_uri' => 'http://10.0.4.30/api-garantias/public/api/loginPC', [
            'headers' => [
                'Authorization' => 'Basic ' . $this->credentials,
            ]]]);
			$response = $client->request('POST', '', [
				'query' => [
					'tag'       => 'login',
					'id'        => $id,
					'password'  => $pass,
				]
				]);
	
			$jsond = json_decode($response->getBody());
		}
		catch(\Exception $e){
			dd($e);
			dd("error");
		}
        /* Si el login es para ejecutar una Garantia */
			if($jsond){
				if($request->input('idGarantiaAEjecutar')){
					$garantia = Garantia::where('id_garantia' , '=', $request->input('idGarantiaAEjecutar'))->first();
					$garantia->cli_asignado = $id;
					$garantia->ejecutada = 1;
					$garantia->fecha_ejecucion = Carbon::now();
					$garantia->save();
					$garantia->success = true;
					return $garantia;
				}
			}
			else{
			}
			
			// dd($request->all(),$jsond);

            return json_encode($jsond);

    }

    public function postEjecutagarantia(Request $request){

				$id = $request->input('cliente');
			
			// 	try {
			// 		if(Auth::User()!=null){
			// 				if(Auth::User()->type != 'admin'){
			// 						return -3;
			// 				}
			// 		}else return -3;
					
			// } catch (\Throwable $th) {
			// 		return -3;
			// }
        /* ejecutar una Garantia */
        if($request->input('idGarantiaAEjecutar')){
            $garantia = Garantia::where('id_garantia' , '=', $request->input('idGarantiaAEjecutar'))->first();
            $garantia->cli_asignado = $id;
            $garantia->ejecutada = 1;
            $garantia->fecha_ejecucion = Carbon::now();
            $garantia->save();
            $ejecutada = true;
        }else{
            $ejecutada = false;
        }

            return json_encode($ejecutada);

    }

    public function postRechazogtiaevento(Request $request){
		
		// 	try {
		// 		if(Auth::User()!=null){
		// 				if(Auth::User()->type != 'admin'){
		// 						return -3;
		// 				}
		// 		}else return -3;
				
		// } catch (\Throwable $th) {
		// 		return -3;
		// }
        $id = $request->input('idGarantiaAEjecutar');
        $observacion = $request->input('observaciones');

        /* Agrego un evento en BD para esta garantía */
        if($request->input('idGarantiaAEjecutar') && $request->input('observaciones')){
            $evento = new Evento;
            $evento->garantia_id = $id;
            $evento->observaciones = $observacion;
            $evento->save();
            $ejecutada = true;
        }else{
            $ejecutada = false;
        }

        return json_encode($ejecutada);

    }

		public function destroy($id)
		{
			try {
				$garantia = Garantia::where('id_garantia', '=', $id)->where('user_id', '=', Auth::user()->id )->first();
				$garantia->delete();

				Flash::info('<i style="font-size:24px;vertical-align: middle;" class="fa fa-trash" aria-hidden="true"></i> <span style="vertical-align:middle;">La garantia <strong>' . $garantia->id_garantia . '</strong> ha sido borrado de manera exitosa.</span>');
				return redirect()->action('GarantiasController@getIndex');
			} catch (\Exception $e) {
				Flash::error('<i style="font-size:24px;vertical-align: middle;" class="fa fa-times" aria-hidden="true"></i> <span style="vertical-align:middle;">Usted no es propietario de la garantia que solicitó eliminar o la misma No existe.</span>');
				return redirect()->action('GarantiasController@getIndex');
				// si salio mal la eliminacion o se quiso eliminar una garantia que no era de ese user/no existia
			}
		}


}

<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../models/Equipo.php';require_once __DIR__.'/../models/Solicitante.php';require_once __DIR__.'/../models/Prestamo.php';require_once __DIR__.'/../services/EquipoService.php';require_once __DIR__.'/../services/PrestamoService.php';
$server=new soap_server();$server->configureWSDL('PrestamoEquipos','urn:PrestamoEquipos');$server->wsdl->schemaTargetNamespace='urn:PrestamoEquipos';
foreach(['Respuesta','Equipo','EquipoLista','Prestamo','PrestamoLista'] as $t){}
$server->wsdl->addComplexType('Respuesta','complexType','struct','all','', ['exito'=>['name'=>'exito','type'=>'xsd:boolean'],'mensaje'=>['name'=>'mensaje','type'=>'xsd:string'],'datos'=>['name'=>'datos','type'=>'xsd:string']]);
$ops=['registrarEquipo'=>['codigo'=>'xsd:string','nombre'=>'xsd:string','tipo'=>'xsd:string','estado'=>'xsd:string'],'consultarEquipo'=>['id'=>'xsd:int'],'listarEquipos'=>[],'actualizarEquipo'=>['id'=>'xsd:int','nombre'=>'xsd:string','tipo'=>'xsd:string','estado'=>'xsd:string'],'registrarPrestamo'=>['equipoId'=>'xsd:int','solicitanteId'=>'xsd:int','fechaPrestamo'=>'xsd:string','fechaEntrega'=>'xsd:string'],'consultarPrestamos'=>[]];
foreach($ops as $name=>$params)$server->register($name,$params,['return'=>'xsd:string'],'urn:PrestamoEquipos','urn:PrestamoEquipos#'.$name,'rpc','encoded',$name);
$db=obtenerConexion();$es=new EquipoService($db,new Equipo($db));$ps=new PrestamoService($db,new Equipo($db),new Solicitante($db),new Prestamo($db));
function xml($x){return '<respuesta><exito>'.($x['exito']?'true':'false').'</exito><mensaje>'.htmlspecialchars($x['mensaje']).'</mensaje><datos>'.htmlspecialchars(json_encode($x['datos'],JSON_UNESCAPED_UNICODE)).'</datos></respuesta>';}
function registrarEquipo($codigo,$nombre,$tipo,$estado){global $es;return xml($es->registrar($codigo,$nombre,$tipo,$estado));}function consultarEquipo($id){global $es;return xml($es->consultar($id));}function listarEquipos(){global $es;return xml($es->listar());}function actualizarEquipo($id,$nombre,$tipo,$estado){global $es;return xml($es->actualizar($id,$nombre,$tipo,$estado));}function registrarPrestamo($equipoId,$solicitanteId,$fechaPrestamo,$fechaEntrega){global $ps;return xml($ps->registrar($equipoId,$solicitanteId,$fechaPrestamo,$fechaEntrega));}function consultarPrestamos(){global $ps;return xml($ps->listar());}
$HTTP_RAW_POST_DATA=file_get_contents('php://input');$server->service($HTTP_RAW_POST_DATA);

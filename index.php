<?php 
function getToken(){
	//creamos el objeto fecha y obtuvimos la cantidad de segundos desde el 1ª enero 1970
	$fecha = date_create();
	$tiempo = date_timestamp_get($fecha);
	//vamos a generar un numero aleatorio
	$numero = mt_rand();
	//vamos a generar ua cadena compuesta
	$cadena = ''.$numero.$tiempo;
	// generar una segunda variable aleatoria
	$numero2 = mt_rand();
	// generar una segunda cadena compuesta
	$cadena2 = ''.$numero.$tiempo.$numero2;
	// generar primer hash en este caso de tipo sha1
	$hash_sha1 = sha1($cadena);
	// generar segundo hash de tipo MD5 
	$hash_md5 = md5($cadena2);
	return substr($hash_sha1,0,20).$hash_md5.substr($hash_sha1,20);
}
include 'DB/conection.php';
require 'vendor/autoload.php';
$f3 = \Base::instance();

$f3->route('GET /',
	function() {
		echo 'Hello, world!';
	}
);
/*
$f3->route('GET /saludo/@nombre',
	function($f3) {
		echo 'Hola '.$f3->get('PARAMS.nombre');
	}
);
*/ 
// Registro
/*
 * Este Registro recibe un JSON con el siguiente formato
 * 
 * { 
 *		"uname": "XXX",
 *		"email": "XXX",
 * 		"password": "XXX"
 * }
 * */

$f3->route('POST /Registro',
	function($f3) {
		/////// obtener el cuerpo de la peticion
		$Cuerpo = $f3->get('BODY');
		$jsB = json_decode($Cuerpo,true);
		/////////////
		$R = array_key_exists('uname',$jsB) && array_key_exists('email',$jsB) && array_key_exists('password',$jsB);
		// TODO checar si estan vacio los elementos del json
		if (!$R){
			echo '{"R":-1}';
			return;
		}
		// TODO validar correo en json
		// TODO Control de error de la $DB
		$db = conection();
		$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		try {
			// Insertar en la tabla Usuario
			$db->exec('INSERT INTO Usuario (uname, email, password) VALUES ("'.$jsB['uname'].'", "'.$jsB['email'].'", MD5("'.$jsB['password'].'"))');
    
			// Obtener el último ID insertado en la tabla Usuario
			$stmt = $db->prepare('SELECT MAX(id) AS max_id FROM Usuario');
			$stmt->execute();
			$id_usuario = $stmt->fetchColumn();
			$stmt->closeCursor();
			
			// Insertar en la tabla Historial
			$stmt = $db->prepare('INSERT INTO Historial (id_usuario, accion, fecha) VALUES (:id_usuario, :accion, NOW())');
			$accion = 'Se registró ' . $jsB['uname'];
			$stmt->bindParam(':id_usuario', $id_usuario, \PDO::PARAM_INT);
			$stmt->bindParam(':accion', $accion, \PDO::PARAM_STR);
			$stmt->execute();
			$stmt->closeCursor();
		} catch (Exception $e) {
			echo '{"R":-2}';
			return;
		}

		echo "{\"R\":0,\"D\":".var_export($R,TRUE)."}";
	}
);





/*
 * Este Registro recibe un JSON con el siguiente formato
 * 
 * { 
 *		"uname": "XXX",
 * 		"password": "XXX"
 * }
 * 
 * Debe retornar un Token 
 * */


$f3->route('POST /Login',
	function($f3) {
		/////// obtener el cuerpo de la peticion
		$Cuerpo = $f3->get('BODY');
		$jsB = json_decode($Cuerpo,true);
		/////////////
		$R = array_key_exists('uname',$jsB) && array_key_exists('password',$jsB);
		// TODO checar si estan vacio los elementos del json
		if (!$R){
			echo '{"R":-1}';
			return;
		}
		// TODO validar correo en json
		// TODO Control de error de la $DB
		$db = conection();
		$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		try {
		// Obtener el ID del Usuario
		$stmt = $db->prepare('SELECT id FROM Usuario WHERE uname = :uname AND password = MD5(:password)');
		$stmt->bindParam(':uname', $jsB['uname'], \PDO::PARAM_STR);
		$stmt->bindParam(':password', $jsB['password'], \PDO::PARAM_STR);
		$stmt->execute();
		$id_usuario = $stmt->fetchColumn();
		$stmt->closeCursor();


		$T = getToken();
		// Insertar en la tabla Historial
		$stmt = $db->prepare('INSERT INTO Historial (id_usuario, accion, fecha) VALUES (:id_usuario, :accion, NOW())');
		$accion = 'Se Loggeo el usuario con id: '.$id_usuario.' y el token: ' . $T;
		$stmt->bindParam(':id_usuario', $id_usuario, \PDO::PARAM_INT);
		$stmt->bindParam(':accion', $accion, \PDO::PARAM_STR);
		$stmt->execute();
		$stmt->closeCursor();


		} catch (Exception $e) {
			echo '{"R":-2}';
			return;
		}
		if (empty($id_usuario)){
			echo '{"R":-3}';
			return;
		}
		
		//file_put_contents('/tmp/log','insert into AccesoToken values('.$R[0].',"'.$T.'",now())');
		$R = $db->exec('Delete from AccesoToken where id_Usuario = "'.$id_usuario.'";');
		$R = $db->exec('insert into AccesoToken values('.$id_usuario.',"'.$T.'",now())');
		echo "{\"R\":0,\"D\":\"".$T."\"}";
	}
);


/*
 * Este subirimagen recibe un JSON con el siguiente formato
 * 
 * { 
 * 		"token: "XXX"
 *		"name": "XXX",
 * 		"data": "XXX",
 * 		"ext": "PNG"
 * }
 * 
 * Debe retornar codigo de estado
 * */

$f3->route('POST /Imagen',
	function($f3) {
		//Directorio
		if (!file_exists('tmp')) {
			mkdir('tmp');
		}
		if (!file_exists('img')) {
			mkdir('img');
		}
		/////// obtener el cuerpo de la peticion
		$Cuerpo = $f3->get('BODY');
		$jsB = json_decode($Cuerpo,true);
		/////////////
		$R = array_key_exists('name',$jsB) && array_key_exists('data',$jsB) && array_key_exists('ext',$jsB) && array_key_exists('token',$jsB);
		// TODO checar si estan vacio los elementos del json
		if (!$R){
			echo '{"R":-1}';
			return;
		}
		
		$db = conection();
		$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		// Validar si el usuario esta en la base de datos
		$TKN = $jsB['token'];
		
		try {
			$R = $db->exec('select id_Usuario from AccesoToken where token = "'.$TKN.'"');
		} catch (Exception $e) {
	
			echo '{"R":-2}';
			return;
		}
		$id_Usuario = $R[0]['id_Usuario'];
		file_put_contents('tmp/'.$id_Usuario,base64_decode($jsB['data']));
		$jsB['data'] = '';
		////////////////////////////////////////////////////////
		////////////////////////////////////////////////////////
		// Guardar info del archivo en la base de datos
		$R = $db->exec('insert into Imagen values(null,"'.$jsB['name'].'","img/",'.$id_Usuario.');');


		// Obtener el último ID insertado en la tabla Imagen
		$stmt = $db->prepare('SELECT MAX(id) AS max_id FROM Imagen where id_Usuario = :id_usuario');
		$stmt->bindParam(':id_usuario', $id_Usuario, \PDO::PARAM_INT);
		$stmt->execute();
		$idImagen = $stmt->fetchColumn();
		$stmt->closeCursor();

		// Insertar en la tabla Historial
		$stmt = $db->prepare('INSERT INTO Historial (id_usuario, accion, fecha) VALUES (:id_usuario, :accion, NOW())');
		$accion = 'El usuario con ID: '.$id_Usuario.' guardó la imgaen con id: '.$idImagen;
		$stmt->bindParam(':id_usuario', $id_Usuario, \PDO::PARAM_INT);
		$stmt->bindParam(':accion', $accion, \PDO::PARAM_STR);
		$stmt->execute();
		$stmt->closeCursor();



		
		$R = $db->exec('update Imagen set ruta = "img/'.$idImagen.'.'.$jsB['ext'].'" where id = '.$idImagen);
		// Mover archivo a su nueva locacion
		rename('tmp/'.$id_Usuario,'img/'.$idImagen.'.'.$jsB['ext']);
		echo "{\"R\":0,\"D\":".$idImagen."}";
	}
);


/*
 * Este Registro recibe un JSON con el siguiente formato
 * 
 * { 
 * 		"token: "XXX",
 * 		"id": "XXX"
 * }
 * 
 * Debe retornar un Token 
 * */


$f3->route('POST /Descargar',
	function($f3) {
		/////// obtener el cuerpo de la peticion
		$Cuerpo = $f3->get('BODY');
		$jsB = json_decode($Cuerpo,true);
		/////////////
		$R = array_key_exists('token',$jsB) && array_key_exists('id',$jsB);
		// TODO checar si estan vacio los elementos del json
		if (!$R){
			echo '{"R":-1}';
			return;
		}
		// TODO validar correo en json
		// Comprobar que el usuario sea valido
		$TKN = $jsB['token'];
		$idImagen = $jsB['id'];
		$db = conection();
		$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		try {
			$R = $db->exec('select id_Usuario from AccesoToken where token = "'.$TKN.'"');
			// Obtener el ID del Usuario
			$stmt = $db->prepare('SELECT id_Usuario FROM AccesoToken WHERE token = :token');
			$stmt->bindParam(':token', $TKN, \PDO::PARAM_STR);
			$stmt->execute();
			$id_usuario = $stmt->fetchColumn();
			$stmt->closeCursor();
		} catch (Exception $e) {
	
			echo '{"R":-2}';
			return;
		}
		
		// Buscar imagen y enviarla
		try {
			$R = $db->exec('Select name,ruta from  Imagen where id = '.$idImagen);
		}catch (Exception $e) {
	
			echo '{"R":-3}';
			return;
		}

		// Insertar en la tabla Historial
		$stmt = $db->prepare('INSERT INTO Historial (id_usuario, accion, fecha) VALUES (:id_usuario, :accion, NOW())');
		$accion = 'El usuario con ID: '.$id_usuario.' descargó la imgaen con id: '.$idImagen;
		$stmt->bindParam(':id_usuario', $id_usuario, \PDO::PARAM_INT);
		$stmt->bindParam(':accion', $accion, \PDO::PARAM_STR);
		$stmt->execute();
		$stmt->closeCursor();

		$web = \Web::instance();
		ob_start();
		// send the file without any download dialog
		$info = pathinfo($R[0]['ruta']);
		$web->send($R[0]['ruta'],NULL,0,TRUE,$R[0]['name'].'.'.$info['extension']);
		$out=ob_get_clean();

		//echo "{\"R\":0,\"D\":\"".$T."\"}";
	}
);


$f3->run();


?>

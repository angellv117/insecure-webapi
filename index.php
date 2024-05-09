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

//Función para comprobar el número mágico de un archivo
//Se sugiere mandar los primeros 12 bytes del archivo
function checkData($ext,$data){
	$hexdata = bin2hex($data);
	if ($ext == 'jpg' || $ext == 'jpeg'){
		if (
			str_starts_with($hexdata,'ffd8ffdb') ||
			str_starts_with($hexdata,'ffd8ffe000104a4649460001') ||
			str_starts_with($hexdata,'ffd8ffee')
		){
			return TRUE;
		}elseif(str_starts_with($hexdata,'ffd8ffe1') && (substr($hexdata,12,12) == '457869660000')){
			return TRUE;
		}else{
			return FALSE;
		}
	}
	elseif ($ext == 'gif'){
		if (
			str_starts_with($hexdata,'474946383761') ||
			str_starts_with($hexdata,'474946383961')
		){
			return TRUE;
		}else{
			return FALSE;
		}
	}
	elseif ($ext == 'png'){
		if (str_starts_with($hexdata,'89504e470d0a1a0a')){
			return TRUE;
		}else{
			return FALSE;
		}
	}
	else{
		return FALSE;
	}
}

//Función para checar que una cadena $data tenga un valor en base64 válido
function validateBase64($data64){
	$str = base64_decode($data64, true);
	if ($str === FALSE) {
		return FALSE;
	}
	else {
		// Even if $str is not FALSE, this does not mean that the input is valid
		$b64 = base64_encode($str);
		// Finally, check if original and re-encoded data are identical, ignoring padding
		if (rtrim($data64, '=') === rtrim($b64, '=')) {
			return $str;
		}
		else{
			return FALSE;
		}
	}
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
 *		"Nombre": "XXX",
 *		"Correo electrónico": "XXX",
 * 		"Contraseña": "XXX"
 * }
 * */

$f3->route('POST /Registro',
	function($f3) {
		/////// obtener el cuerpo de la peticion
		$Cuerpo = $f3->get('BODY');
		$jsB = json_decode($Cuerpo,true);
		/////////////
		$R = array_key_exists('Nombre',$jsB) && array_key_exists('Correo electrónico',$jsB) && array_key_exists('Contraseña',$jsB);
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
			$stmt = $db->prepare('INSERT INTO Usuario (uname, email, password) VALUES (:uname, :email, MD5(:password))');
			$stmt->bindParam(':uname', $jsB['Nombre'], \PDO::PARAM_STR);
			$stmt->bindParam(':email', $jsB['Correo electrónico'], \PDO::PARAM_STR);
			$stmt->bindParam(':password', $jsB['Contraseña'], \PDO::PARAM_STR);
			$stmt->execute();
			$stmt->closeCursor();
    
			// Obtener el último ID insertado en la tabla Usuario
			$stmt = $db->prepare('SELECT MAX(id) AS max_id FROM Usuario');
			$stmt->execute();
			$id_usuario = $stmt->fetchColumn();
			$stmt->closeCursor();
			
			// Insertar en la tabla Historial
			$stmt = $db->prepare('INSERT INTO Historial (id_usuario, accion, fecha) VALUES (:id_usuario, :accion, NOW())');
			$accion = 'Se registró ' . $jsB['Nombre'];
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
 * Este Login recibe un JSON con el siguiente formato
 * 
 * { 
 *		"Nombre": "XXX",
 * 		"Contraseña": "XXX"
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
		$R = array_key_exists('Nombre',$jsB) && array_key_exists('Contraseña',$jsB);
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
		$stmt->bindParam(':uname', $jsB['Nombre'], \PDO::PARAM_STR);
		$stmt->bindParam(':password', $jsB['Contraseña'], \PDO::PARAM_STR);
		$stmt->execute();
		$id_usuario = $stmt->fetchColumn();
		$stmt->closeCursor();

		//Verificar que el resultado en $id_usuario tenga el formato correcto
		if (!(is_numeric($id_usuario) && $id_usuario >= 0)){
			echo '{"R":-4}';
			return;
		}

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
		$stmt = $db->prepare('DELETE FROM AccesoToken WHERE id_Usuario = :id_usuario');
		$stmt->bindParam(':id_usuario', $id_usuario, \PDO::PARAM_INT);
		$stmt->execute();
		$stmt->closeCursor();
		$stmt = $db->prepare('INSERT INTO AccesoToken VALUES (:id_usuario, :token, now())');
		$stmt->bindParam(':id_usuario', $id_usuario, \PDO::PARAM_INT);
		$stmt->bindParam(':token', $T, \PDO::PARAM_STR);
		$stmt->execute();
		$stmt->closeCursor();
		echo "{\"R\":0,\"D\":\"".$T."\"}";
	}
);


/*
 * Este subirimagen recibe un JSON con el siguiente formato
 * 
 * { 
 * 		"tkn: "XXX"
 *		"nombre": "XXX",
 * 		"data": "XXX",
 * 		"ext": "jpg|jpeg|gif|png"
 * }
 * 
 * Debe retornar codigo o mensaje de estado
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
		$R = array_key_exists('nombre',$jsB) && array_key_exists('data',$jsB) && array_key_exists('ext',$jsB) && array_key_exists('tkn',$jsB);
		// TODO checar si estan vacio los elementos del json
		if (!$R){
			echo '{"R":-1}';
			return;
		}
		
		//Validar tamaño máximo de datos de imagen (1398104 caracteres en base64, 1 MB)
		if (strlen($jsB['data']) > 1398104){
			echo '{"R":-4}';
			return;
		}
		
		//Validar que los datos sean una cadena base64 válida
		//$data almacena la cadena en base64 decodificada o FALSE
		$data = validateBase64($jsB['data']);
		if ($data === FALSE){
			echo '{"R":-5}';
			return;
		}
		$jsB['data'] = '';
		
		//Revisar que la extensión y los datos del archivo sean válidos
		$extensiones = ['jpg','jpeg','gif','png'];
		if (!(in_array(strtolower($jsB['ext']),$extensiones,TRUE) && checkData(strtolower($jsB['ext']),substr($data,0,12)))){
			echo '{"R":-3}';
			return;
		}
		
		$db = conection();
		$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		// Validar si el usuario esta en la base de datos
		$TKN = $jsB['tkn'];
		
		try {
			$stmt = $db->prepare('SELECT id_Usuario FROM AccesoToken WHERE token = :token');
			$stmt->bindParam(':token', $TKN, \PDO::PARAM_STR);
			$stmt->execute();
			$id_Usuario = $stmt->fetchColumn();
			$stmt->closeCursor();
		} catch (Exception $e) {
	
			echo '{"R":-2}';
			return;
		}
		//Crea un archivo temporal en la carpeta tmp con el prefijo img
		$tempName = tempnam('tmp', 'img');
		file_put_contents($tempName,$data);
		
		////////////////////////////////////////////////////////
		////////////////////////////////////////////////////////
		// Guardar info del archivo en la base de datos
		$stmt = $db->prepare('INSERT INTO Imagen VALUES (NULL, :nombre, "img/", :id_Usuario);');
		$stmt->bindParam(':nombre', $jsB['nombre'], \PDO::PARAM_STR);
		$stmt->bindParam(':id_Usuario', $id_Usuario, \PDO::PARAM_INT);
		$stmt->execute();
		$stmt->closeCursor();


		// Obtener el último ID insertado en la tabla Imagen
		$stmt = $db->prepare('SELECT MAX(id) AS max_id FROM Imagen where id_Usuario = :id_usuario');
		$stmt->bindParam(':id_usuario', $id_Usuario, \PDO::PARAM_INT);
		$stmt->execute();
		$idImagen = $stmt->fetchColumn();
		$stmt->closeCursor();

		// Insertar en la tabla Historial
		$stmt = $db->prepare('INSERT INTO Historial (id_usuario, accion, fecha) VALUES (:id_usuario, :accion, NOW())');
		$accion = 'El usuario con ID: '.$id_Usuario.' guardó la imagen con id: '.$idImagen;
		$stmt->bindParam(':id_usuario', $id_Usuario, \PDO::PARAM_INT);
		$stmt->bindParam(':accion', $accion, \PDO::PARAM_STR);
		$stmt->execute();
		$stmt->closeCursor();

		//Actualizar ruta en la base de datos
		$ruta = 'img/'.$idImagen.'.'.$jsB['ext'];
		$stmt = $db->prepare('UPDATE Imagen SET ruta = :ruta WHERE id = :idImagen');
		$stmt->bindParam(':ruta', $ruta, \PDO::PARAM_STR);
		$stmt->bindParam(':idImagen', $idImagen, \PDO::PARAM_INT);
		$stmt->execute();
		$stmt->closeCursor();
		
		// Mover archivo a su nueva locacion
		rename($tempName,$ruta);
		echo "{\"R\":0,\"D\":\"Correcto\"}";
	}
);


/*
 * Esta función recibe un JSON con el siguiente formato
 * 
 * { 
 * 		"tkn: "XXX"
 * }
 * 
 * Debe retornar una lista de los nombres de las imágenes subidas por el usuario dueño del token
 * */

$f3->route('POST /Lista_imagenes',
	function($f3) {
		/////// obtener el cuerpo de la peticion
		$Cuerpo = $f3->get('BODY');
		$jsB = json_decode($Cuerpo,true);
		
		//Comprobar que se encuentra el token en el JSON
		$R = array_key_exists('tkn',$jsB);
		// TODO checar si estan vacio los elementos del json
		if (!$R){
			echo '{"R":-1}';
			return;
		}
		
		//Inicializar conexión a base de datos
		$db = conection();
		$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		
		//Obtener ID de usuario correspondiente al token
		$TKN = $jsB['tkn'];
		try {
			$stmt = $db->prepare('select id_Usuario from AccesoToken where token = :token');
			$stmt->bindParam(':token', $TKN, \PDO::PARAM_STR);
			$stmt->execute();
			$id_Usuario = $stmt->fetchColumn();
			$stmt->closeCursor();
		} catch (Exception $e) {
			echo var_export($TKN,TRUE);
			echo var_export($id_Usuario,TRUE);
			echo '{"R":-2}';
			return;
		}

		//Obtener lista de imágenes subidas por el usuario
		try{
			$stmt = $db->prepare('SELECT name FROM Imagen where id_Usuario = :id_usuario');
			$stmt->bindParam(':id_usuario', $id_Usuario, \PDO::PARAM_INT);
			$stmt->execute();
			$lista = $stmt->fetchAll(\PDO::FETCH_COLUMN,0);
			$stmt->closeCursor();
		}catch (Exception $e) {
			echo '{"R":-3}';
			return;
		}

		// Insertar en la tabla Historial el registro de actividad
		$stmt = $db->prepare('INSERT INTO Historial (id_usuario, accion, fecha) VALUES (:id_usuario, :accion, NOW())');
		$accion = 'El usuario con ID: '.$id_Usuario.' consultó su lista de imágenes';
		$stmt->bindParam(':id_usuario', $id_Usuario, \PDO::PARAM_INT);
		$stmt->bindParam(':accion', $accion, \PDO::PARAM_STR);
		$stmt->execute();
		$stmt->closeCursor();

		echo "{\"R\":0,\"D\":".json_encode($lista)."}";
	}
);


/*
 * Este Registro recibe un JSON con el siguiente formato
 * 
 * { 
 * 		"tkn: "XXX",
 * 		"nombre": "XXX"
 * }
 * 
 * Realiza la descarga de una imagen
 * */


$f3->route('POST /Descargar',
	function($f3) {
		/////// obtener el cuerpo de la peticion
		$Cuerpo = $f3->get('BODY');
		$jsB = json_decode($Cuerpo,true);
		/////////////
		$R = array_key_exists('tkn',$jsB) && array_key_exists('nombre',$jsB);
		// TODO checar si estan vacio los elementos del json
		if (!$R){
			echo '{"R":-1}';
			return;
		}
		// TODO validar correo en json
		// Comprobar que el usuario sea valido
		$TKN = $jsB['tkn'];
		$nombre = $jsB['nombre'];
		$db = conection();
		$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		try {
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
			$stmt = $db->prepare('SELECT id, ruta FROM Imagen WHERE name = :nombre AND id_Usuario = :id_usuario');
			$stmt->bindParam(':nombre', $nombre, \PDO::PARAM_STR);
			$stmt->bindParam(':id_usuario', $id_usuario, \PDO::PARAM_INT);
			$stmt->execute();
			$result = $stmt->fetch(\PDO::FETCH_ASSOC);
			$stmt->closeCursor();
		}catch (Exception $e) {
	
			echo '{"R":-3}';
			return;
		}
		$idImagen = $result['id'];
		$ruta = $result['ruta'];

		// Insertar en la tabla Historial
		$stmt = $db->prepare('INSERT INTO Historial (id_usuario, accion, fecha) VALUES (:id_usuario, :accion, NOW())');
		$accion = 'El usuario con ID: '.$id_usuario.' descargó la imagen con id: '.$idImagen;
		$stmt->bindParam(':id_usuario', $id_usuario, \PDO::PARAM_INT);
		$stmt->bindParam(':accion', $accion, \PDO::PARAM_STR);
		$stmt->execute();
		$stmt->closeCursor();

		$web = \Web::instance();
		ob_start();
		// send the file without any download dialog
		$info = pathinfo($ruta);
		$web->send($ruta,NULL,0,TRUE,$nombre.'.'.$info['extension']);
		$out=ob_get_clean();

		//echo "{\"R\":0,\"D\":\"".$T."\"}";
	}
);


$f3->run();


?>

<?php

class Application extends Config {

    private $routingRules = [
        'Application' => [
            'index' => 'Application/actionIndex'
        ],
        'robots.txt' => [
            'index' => 'Application/actionRobots'
        ],
        'debug' => [
            'index' => 'Application/actionDebug'
        ]
    ];

    /**
     * @var $view View
     */
    private $view;

    function __construct() {
        parent::__construct();
        $this->view = new View($this);
        if ($this->requestMethod == 'POST') {
            header('Content-Type: application/json');
            die(json_encode($this->ajaxHandler($_POST)));
        } else {
            //Normal GET request. Nothing to do yet
        }
    }

    public function run() {
        if (array_key_exists($this->routing->controller, $this->routingRules)) {
            if (array_key_exists($this->routing->action, $this->routingRules[$this->routing->controller])) {
                list($controller, $action) = explode(DIRECTORY_SEPARATOR, $this->routingRules[$this->routing->controller][$this->routing->action]);
                call_user_func([$controller, $action]);
            } else { http_response_code(404); die('action not found'); }
        } else { http_response_code(404); die('controller not found'); }
    }

    public function actionIndex() {
        return $this->view->render('index');
    }

    public function actionDebug() {
        return $this->view->render('debug');
    }

    public function actionRobots() {
        return implode(PHP_EOL, ['User-Agent: *', 'Disallow: /']);
    }

    /**
     * Здесь нужно реализовать механизм валидации данных формы
     * @param $data array
     * $data - массив пар ключ-значение, генерируемое JavaScript функцией serializeArray()
     * name - Имя, обязательное поле, не должно содержать цифр и не быть больше 64 символов
     * phone - Телефон, обязательное поле, должно быть в правильном международном формате. Например +38 (067) 123-45-67
     * email - E-mail, необязательное поле, но должно быть либо пустым либо содержать валидный адрес e-mail
     * comment - необязательное поле, но не должно содержать тэгов и быть больше 1024 символов
     *
     * @return array
     * Возвращаем массив с обязательными полями:
     * result => true, если данные валидны, и false если есть хотя бы одна ошибка.
     * error => ассоциативный массив с найдеными ошибками,
     * где ключ - name поля формы, а значение - текст ошибки (напр. ['phone' => 'Некорректный номер']).
     * в случае отсутствия ошибок, возвращать следует пустой массив
     */
    public function actionFormSubmit($data) {

        $errors = [];                                  //Отсутствие ошибок
        $name=$data[0]['value']; //переменная значения поля name
        $phone=$data[1]['value'];//переменная значения поля phone
        $email=$data[2]['value'];//переменная значения поля email
        $comment=$data[3]['value'];//переменная поля comment
        
        if($name=='') //проверка имени на наличие значения
            $errors['name']='поле обязательное';
	    if(preg_match('/\d/',$name)) //проверка имени на наличие цифр
	       $errors['name']='цифры запрещены';
		if(strlen($name)>64) //проверка имени на количество символов
		    $errors['name']='количество символов должно быть меньше или равно 64';
		
		if($phone=='') //проверка телефона на наличие значение
		    $errors['phone']='поле обязательное';
		
		if(!($email=='')) //проверка имейла на валидность
		     if(!filter_var($email, FILTER_VALIDATE_EMAIL))
		        $errors['email']='введите валидный email или оставте поле пустым';
		
		if(strlen($comment)>1024)$errors['comment']='количество символов должно быть меньше 1024';//проверка комментария на количество символов
		

        if(count($errors)>0) return ['result' => false, 'error' => $errors];//возврат результата с ошибками
        else return['result'=>true, []];//возврат результата без ошибок
    }


    /**
     * Функция обработки AJAX запросов
     * @param $post
     * @return array
     */
    private function ajaxHandler($post) {
        if (count($post)) {
            if (isset($post['method'])) {
                switch($post['method']) {
                    case 'formSubmit': $result = $this->actionFormSubmit($post['data']);
                        break;
                    default: $result = ['error' => 'Unknown method']; break;
                }
            } else { $result = ['error' => 'Unspecified method!']; }
        } else { $result = ['error' => 'Empty request!']; }
        return $result;
    }
}

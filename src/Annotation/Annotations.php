<?php

namespace Agrimedia\Anhpt\Annotation;
use Illuminate\Support\Facades\File;
use \ReflectionClass;
use \ReflectionMethod;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

class Annotations implements AnnotationsInterface
{

    const CONTROLLER_NAMESPACE = 'Modules\{$mo}\Http\Controllers';
    const MODEL_NAMESPACE = 'App\\';
    protected $annotations = null;

    function read($cache = false)
    {
        $permissions = [];
        if ($cache && file_exists('swagger.json')) {
            $list_role = file_get_contents('swagger.json');
            return json_decode($list_role, true);

        } else {
            if (file_exists('swagger.json')) unlink('swagger.json');
            if (file_exists('permission.json')) unlink('permission.json');
            $list_modules = $this->getFolderModule();
            $files = $this->getFileController($list_modules);
            $annotationReader = new SimpleAnnotationReader();
            $annotationReader->addNamespace('Core');

            $list_role = [];
            foreach ($files as $file) {
                $length = strlen($file->getFilename());
                /*Lấy tên của của file bỏ đi đuôi .php */
                $className = substr($file->getFilename(), 0, $length - 4);
                $modelClass = substr($className, 0, strlen($className) - 10);
                $relativePath = $file->getrelativePath() == "" ? "" : $file->getrelativePath() . "\\";
                /*Kiểm tra xem file có hậu tố là Controller.php */
                if (substr($file->getFilename(), $length - 14, $length) == "Controller.php") {
                    $namespaceClass = str_replace('{$mo}', $modelClass, self::CONTROLLER_NAMESPACE) . $relativePath . "\\" . $className;
                    /*Tạo 1 instance cho ReflectionClass để đọc annotations*/
                    $reflectionClass = new ReflectionClass($namespaceClass);

                    AnnotationRegistry::loadAnnotationClass(\Core\AnnotatedDescription::class);
                    $classAnnotations = $annotationReader->getClassAnnotations($reflectionClass);

                    /* Kiểm tra có annotation hay không và chế độ cho phép hay không của method */
                    if (count($classAnnotations) > 0 && $classAnnotations[0]->allow) {

                        $array_methods = [];
                        $methods = get_class_methods($namespaceClass);

                        foreach ($methods as $k => $method) {
                            /*Tạo 1 instance cho ReflectionMethod để đọc annotations*/
                            $reflectionMethod = new ReflectionMethod($namespaceClass, $method);
                            $method_annotation = $annotationReader->getMethodAnnotations($reflectionMethod);
                            /* Kiểm tra có annotation hay không và chế độ cho phép hay không của method */
                            if ($method_annotation && $method_annotation[0]->allow) {
                                $methodName = $namespaceClass . "@" . $method;

                                $action = \Route::getRoutes()->getByAction($methodName);
                                if ($action) {
                                    $action = $action->getAction();

                                    if (sizeof($action) > 0 && isset($action['as'])) {

                                        $temp_anno = array(
                                            'code' => $action['as'],
                                            'desc' => $method_annotation[0]->desc,
                                        );
                                    } else {
                                        $temp_anno = array(
                                            'code' => strtolower($modelClass) . "." . $method,
                                            'desc' => $method_annotation[0]->desc,
                                        );
                                    }
                                    array_push($array_methods, $temp_anno);
                                    array_push($permissions, $temp_anno['code']);
                                }

                            } else {
                                $this->error('method');
                            }
                        }
                        $groups = [];
                        if ($classAnnotations[0]->group) {
                            $groups = $this->getDataFromDb($modelClass);
                        }
                        $list_role[] = array(
                            'code' => $className,
                            'desc' => $classAnnotations[0]->desc,
                            'groups' => $groups,
                            'methods' => $array_methods
                        );


                    } else {
                        $this->error('class');
                    }


                }

            }
            $json_data = json_encode($list_role);
            file_put_contents('swagger.json', $json_data);
            file_put_contents('permission.json', json_encode($permissions));
            return $list_role;
        }


    }

    private function getFolderModule()
    {
        $directories = glob(base_path() . '/Modules/*', GLOB_ONLYDIR);
        return array_map(function ($dir) {
            $dir = explode("/", $dir);
            return $dir[count($dir) - 1];
        }, $directories);
    }

    private function getFileController($folders)
    {
        $files = [];
        foreach ($folders as $folder) {
            $directory = base_path('Modules/') . $folder . "/Http/Controllers";
            $file = File::allFiles($directory);
            if (is_array($file)) {
                foreach ($file as $fi) {
                    array_push($files, $fi);
                }
            }

        }
        return $files;
    }

    protected function error($type, $data = null)
    {
        /* Xử lý khi không đọc được thông tin */
    }

    protected function getDataFromDb($className)
    {
        /*        $model = self::MODEL_NAMESPACE . $className;
                $data = $model::query()->get()->toArray();
                return $data;*/
    }

    function render()
    {

    }
}
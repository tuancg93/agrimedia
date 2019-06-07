<?php

namespace Agrimedia\Anhpt\Annotation;


/**
 * @Annotation
 */
class AnnotatedDescription
{


    /*
     *  allow = true => Cho phép quét ,
     *  allow = false => Bỏ qua không quét
     */
    public $allow = true;

    /*
    *   Mô tả về tên của chức năng
    */
    public $desc;

    /*
     * Class có cần phân quyền chi tiết đến từng mục
     * $group = true => Class này cần chia đến từng mục
     * $group => false => Ngược lại
     * default = false
    */
    public $group = false;

    /*
     * id của session lưu group
     *
    */
    public $group_id ;
}
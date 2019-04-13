<?php
namespace app\common\validate;

use think\Validate;

class Business extends Validate
{
    protected $rule =   [
        'business_name'=>  'require|unique:business,business_name|length:2,50',
        'business_level'=>  'require|checkFather',
        'business_description'=> 'max:200',
    ];
    
    protected $message  =   [
        'business_name.require'=>	'业务名称必填',
        'business_name.length'=>  '名称长度在2-50之间',
        'business_name.unique'=>  '业务名称不能重复',
        'business_level.require'=>  '层级必须设置',
        'business_description.max'=>  '业务描述不能超过200个字',
    ];
    
    protected $scene = [
        'init'          =>  [],
    ];

    protected function checkFather($value,$rule,$data)
    {
        if(in_array($data['business_level'],['2','3'])) {
            if(!array_key_exists('business_father_id', $data) || !$data['business_father_id']) {
                return '层级大于1时必须设置上级业务';
            } else {
                return true;
            }
        } else {
            return true;
        }
    }
}
<?php
namespace app\common\validate;

use think\Validate;

class Merchant extends Validate
{
    protected $rule =   [
        'merchant_name'=>  'require|unique:merchant,merchant_name|length:3,100',
        'merchant_password'=>	'require|confirm|length:4,25|alphaDash',
        'merchant_email'=>  'require|unique:merchant,merchant_email|email',
        'merchant_mobile'=> 'require|unique:merchant,merchant_mobile|number',
    ];
    
    protected $message  =   [
        'merchant_name.require'=>	'商户名称必填',
        'merchant_name.length'=>  '商户名称长度在3-100之间',
        'merchant_name.unique'=>  '商户名称已经存在',
        'merchant_email.require'=>  '邮箱必填',
        'merchant_email.email'=>  '邮箱格式要填写正确',
        'merchant_email.unique'=>  '邮箱已经存在',
        'merchant_mobile.require'=>  '手机号必填',
        'merchant_mobile.number'=>  '请填写正确的手机号',
        'merchant_mobile.unique'=>  '手机号已经存在',
        'merchant_password.require'=>	'密码是必填项',
        'merchant_password.confirm'=>  '两次输入的密码不一致',
        'merchant_password.length'=>  '密码的长度在4-25之间',
        'merchant_password.alphaDash'=>  '密码只允许包含字母、数字、下划线或减号',
    ];
    
    protected $scene = [
        'init'          =>  [],
        'password'      =>  ['merchant_password'],
        'nopassword'    =>  ['merchant_name','merchant_email','merchant_mobile'],
        'login'         =>  ['merchant_email'=>'require|email','merchant_password'=>'require|length:4,25|alphaDash'],
    ];
}
// common.admin.js
// 
/**
 * 执行jquery的ajax请求，返回后台传回的结果
 * 依赖jquery和layer库
 * @param  {[type]} url  请求的url
 * @param  {[type]} data 需要向后台传递的数据
 * @return {[type]}      无返回值，如果后台执行成功则前台刷新页面
 */
function ajax_post(url,data)
{
	$.ajax({
        type: "POST",	// POST方法提交
        url: url,	// 执行的方法
        data: data,	// 
        dataType: "json",	// 数据类型为json
        success: function(result) {
        	// 当ajax请求执行成功时执行
            if (result.status == true) {
            	// 返回result对象中的status元素值为1表示数据插入成功
                layer.msg(result.message, {icon: 6, time: 2000});	// 使用H-ui的浮动提示框，2秒后自动消失
				setTimeout(function() {
					parent.location.reload();
				}, 2000);	//2秒后对父页面执行刷新（相当于关闭了弹层同时更新了数据）
            } else {
            	// 返回result对象的status值不为1，表示数据插入失败
            	layer.msg(result.message, {icon: 5, time: 2000});	// 使用H-ui的浮动提示框，2秒后自动消失
            	// 页面停留在这里，不再执行任何动作
            }
        }
	});
}

/**
 * 执行ajax_post前增加一步用户确认操作
 * 依赖jquery和layer库
 * @param  {[type]} url      要请求的url
 * @param  {[type]} data     要上传的数据
 * @param  {[type]} action   在确认框中要显示的动作名称，如：删除
 * @param  {[type]} tar_name 在确认框中要显示的对象名称，一般传递object_name
 * @return {[type]}          无
 */
function ajax_post_confirm(url, data, tar_name, action) {
    // 用layer.confirm插件进行操作确认
    layer.confirm("确认要对 <span class='c-red'>"+tar_name+"</span> 进行 <span class='c-blue'>"+action+"</span> 操作吗？", function() {
        // 当layer.confirm被确认后开始提交操作
        // $.get(url, data, success)
        ajax_post(url, data);
    });
}
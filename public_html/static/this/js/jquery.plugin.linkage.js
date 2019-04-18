/**
 * 通过ajax调用后台url读取数据库数据生成无限级联下拉菜单
 * 对后台url要求：参数名称为father_id
 * 使用方法：
 *  HTML：
 *  <div id="container"></div>
 *  JS：
 * 	$("#container").linkage({
 *   	url: "{:url('index/service/district')}",
 *		root_father: 86,
 *		data:[210000,210100,210104],
 *		level:2,
 *		value_key:"district_id",
 *		text_key:"district_name"
 *	});
 *	
 * TODO：
 * 设定默认值时，加载顺序会有问题
 */
;(function($) {
	$.fn.linkage = function(options) {
		// 默认参数设置
		var settings = {
			url: "/index/service/district",	// 请求路径
			data: [],	// 初始ID值
			split: "|",             //分割符
			root_father: "0",	// 最高级的父ID
			level: -1,	// 加载级别，小于等0表示加载到底
			cssName: "form-control",	// 样式名称，默认为bootstrap的form-control
			value_key: "district_id",	// <option value="id">name</option>
			text_key: "district_name",
			hiddenName: "id_array"	// 隐藏域的name属性
		}
		// 合并参数
		if(options) {
			$.extend(settings, options);
		}
		// 链式原则
		return this.each(function() {
			init($(this), settings.root_father, settings.data);
			/**
			 * 初始化
			 * @param  {[type]} container 容器对象
			 * @param  {[type]} data      初始值
			 * @return {[type]}           [description]
			 */
			function init(container, root_father, data) {
				// 创建隐藏域，赋初始值
				var _input = $("<input type='hidden' name='"+settings.hiddenName+"'/>").appendTo(container).val(settings.data);
				var arr = [root_father];
				arr.push.apply(arr,data);
				for(var i=0; i<arr.length; i++) {
					// 创建下拉框
					createSelect(container, arr[i], arr[i+1]||-1);
				}
			}
			/**
			 * 创建下拉框
			 * @param  {[type]} container 容器对象
			 * @param  {[type]} father_id  父ID
			 * @param  {[type]} id        自身ID
			 * @return {[type]}           [description]
			 */
			function createSelect(container, father_id, id) {
				// 加载条件1或：当前已加载级别未达到设定最大级别
				// 加载条件2或：设定最大级别不大于0
				// 加载条件3和：已选父级ID大于等于0
				// 加载条件4和：有子级
				if(
					($("select", container).length<settings.level || settings.level<=0)
					&& father_id!="-1") {
					// 创建select对象，并将select对象放入container内
					var _select = $("<select></select>");
					// 如果father_id为空，则_fid值为86
					var _fid = father_id || "0";
					//发送ajax请求，返回的data必须为json格式
					$.getJSON(settings.url, {father_id: _fid}, function(data) {
						if(data.length>0) {
							// 添加节点
							addOptions(container, _select, data).val(id||-1);
							_select.appendTo(container).addClass(settings.cssName);
						}
						saveVal(container);
					});
				} else {
					saveVal(container);
				}
			}
			/**
			 * 为下拉框添加<option>
			 * @param {[type]} container 容器对象
			 * @param {[type]} select    下拉框
			 * @param {[type]} data      JSON格式数据
			 */
			function addOptions(container, select, data) {
				select.append($('<option value="-1">请选择</option>'));
				for(var i=0; i<data.length; i++) {
					select.append($('<option value="'+data[i][settings.value_key]+'">'+data[i][settings.value_key]+data[i][settings.text_key]+'</option>'));
				}
				// 为select绑定change事件
				select.bind("change", function() {
					_onchange(container, $(this), $(this).val());
				});
				return select;
			}
			/**
			 * select的change事件
			 * @param  {[type]} container 容器对象
			 * @param  {[type]} select    下拉框对象
			 * @param  {[type]} id        当前下拉框的值
			 * @return {[type]}           [description]
			 */
			function _onchange(container, select, id) {
				var nextAll = select.nextAll("select");
				// 如果当前select对象的值是空或-1，则将其后面的select对象全部移除
				if(!id || id=="-1" || nextAll.length>0) {
					nextAll.remove();
				}
				// saveVal(container);
				createSelect(container, id, -1);
			}
			/**
			 * 将选择的值保存在隐藏域中
			 * @param  {[type]} container 容器对象
			 * @return {[type]}           [description]
			 */
			function saveVal(container) {
				var arr = new Array();
				//arr.push(86);	// 为数组arr添加元素0
				$("select", container).each(function() {
					if($(this).val()!="-1") {
						arr.push($(this).val());	// 获取container下每个select对象的值并添加到数组arr中
					}
				});
				// 为隐藏域对象赋值
				$("input[name='"+settings.hiddenName+"']",container).val(arr.join(settings.split));
				console.log($("input[name='"+settings.hiddenName+"']",container).val());
			}
		});
	}
})(jQuery);
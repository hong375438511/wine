define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    const provinceSelect = function() {
        return new Promise(resolve => {
            $.getJSON('unishop/area/getSelect').done(res => {
                resolve(res);
            });
        });
    }

    var Controller = {
        index: async function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'unishop/address/index',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                //启用固定列
                fixedColumns: true,
                //固定右侧列数
                fixedRightNumber: 1,
                fixedLeftNumber: 1,
                searchFormVisible: true,
                showToggle: false,
                showColumns: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'user_id', title: __('User Id')},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'province_id', title: __('Province Name'), searchList: await provinceSelect(), visible: false, addclass: 'provinceChange'},
                        {field: 'province.name', title: __('Province Name'), operate: false},
                        {field: 'city_id', title: __('City Name'), searchList: {}, visible: false, addclass: 'cityChange'},
                        {field: 'city.name', title: __('City Name'), operate: false},
                        {field: 'area_id', title: __('Area Name'), searchList: {}, visible: false, addclass: 'areaChange'},
                        {field: 'area.name', title: __('area Name'), operate: false},
                        {field: 'address', title: __('Address'),operate: false},
                        {field: 'is_default', title: __('Is Default'), searchList: {'0':__('No'),'1': __('Yes')}, visible: false},
                        {field: 'is_default_name', title: __('Is Default'),operate: false},
                        {field: 'createtime', title: __('Create Time'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'updatetime', title: __('Update Time'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                    ]
                ]
            });

            $(document).on('change', '.provinceChange', function() {
                $(".cityChange").empty();
                $(".cityChange").append("<option value=''>"+ __('Choose') +"</option>");
                $(".areaChange").empty();
                $(".areaChange").append("<option value=''>"+ __('Choose') +"</option>");
                var pid = $(".provinceChange").val();
                getCityAndArea(pid,'.cityChange');

            });

            $(document).on('change', '.cityChange', function() {
                $(".areaChange").empty();
                $(".areaChange").append("<option value=''>"+ __('Choose') +"</option>");
                var pid = $(".cityChange").val();
                getCityAndArea(pid,'.areaChange');
            });

            function getCityAndArea(pid,className){
                if(pid>0){
                    var arr = [];
                    $.get("unishop/area/getSelect", {pid: pid}, function(res) {
                        for (let i in res) {
                            arr.push({
                                id: i,
                                value: res[i]
                            });
                        }

                        if (arr.length > 0) {
                            for (var i=0; i<arr.length; i++) {
                                $(className).append("<option value='"+ arr[i]['id'] +"'>"+ arr[i]['value'] +"</option>");
                            }
                        }
                    });

                }
            }

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
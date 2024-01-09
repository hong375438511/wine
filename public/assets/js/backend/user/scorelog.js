define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/scorelog/index',
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
                        {field: 'user.username', title: __('Username'), operate: 'LIKE'},
                        {field: 'user.nickname', title: __('Nickname'), operate: 'LIKE'},
                        /*{field: 'email', title: __('Email'), operate: 'LIKE'},*/
                        {field: 'score', title: __('Score'), operate: 'BETWEEN', sortable: true},
                        {field: 'before', title: __('Before'), operate: 'BETWEEN', sortable: true},
                        {field: 'after', title: __('After'), operate: 'BETWEEN', sortable: true},
                        {field: 'memo', title: __('Memo'), operate: 'LIKE', sortable: true},
                        {field: 'createtime', title: __('Create Time'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                    ]
                ]
            });

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
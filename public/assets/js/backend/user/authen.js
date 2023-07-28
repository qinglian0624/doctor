define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/authen/index' + location.search,
                    add_url: 'user/authen/add',
                    edit_url: 'user/authen/edit',
                    del_url: 'user/authen/del',
                    multi_url: 'user/authen/multi',
                    import_url: 'user/authen/import',
                    table: 'user_authen',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'card_id', title: __('Card_id'), operate: 'LIKE'},
                        {field: 'idcard_head', title: __('Idcard_head'),events: Table.api.events.image, formatter: Table.api.formatter.images},
                        {field: 'idcard_back', title: __('Idcard_back'),events: Table.api.events.image, formatter: Table.api.formatter.images},
                        {field: 'bankid', title: __('Bankid')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'state', title: __('State')},
                        {field: 'state', title: __('State'),formatter: Table.api.formatter.status, searchList: {0: __('审核中'), 1: __('通过'), 2: __('拒绝')}},

                        {field: 'user.nickname', title: __('User.nickname'), operate: 'LIKE'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

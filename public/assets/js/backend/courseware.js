define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'courseware/index' + location.search,
                    add_url: 'courseware/add',
                    edit_url: 'courseware/edit',
                    del_url: 'courseware/del',
                    multi_url: 'courseware/multi',
                    import_url: 'courseware/import',
                    table: 'courseware',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'category1.name', title: __('教育方向')},
                        {field: 'category2.name', title: __('教育领域')},
                        {field: 'project.name', title: __('教育类型'), operate: 'LIKE'},
                        {field: 'cours_name', title: __('Cours_name'), operate: 'LIKE'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'video_status', title: __('Video_status')},
                        {field: 'video_status', title: __('video_status'),formatter: Table.api.formatter.status, searchList: {0: __('审核中'), 1: __('通过'), 2: __('拒绝')}},

                        {field: 'sign_img', title: __('Sign_img'),events: Table.api.events.image, formatter: Table.api.formatter.images},
                        {field: 'ppt_status', title: __('ppt_status'),formatter: Table.api.formatter.status, searchList: {0: __('审核中'), 1: __('通过'), 2: __('拒绝')}},

                        // {field: 'ppt_status', title: __('Ppt_status')},
                        {field: 'video_name', title: __('Video_name'), operate: 'LIKE'},
                        {field: 'user.nickname', title: __('User.nickname'), operate: 'LIKE'},
                        {field: 'category.name', title: __('Category.name'), operate: 'LIKE'},

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

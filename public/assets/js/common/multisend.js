/**
 * 图片/视频多选发送助手
 * 依赖 jQuery。各端在 file input 的 onchange 中调用 MultiSend.pick(input, opts)：
 *   opts.type : 'image' | 'video'
 *   opts.send : function(file, onOk, onFail) 由调用方实现单个文件的上传+发送逻辑
 * 选择文件后弹出预览面板，确认后逐个串行调用 opts.send 发送。
 * 部分手机浏览器的系统相册不支持一次勾选多张，此时可用预览面板中的“+”
 * 按钮多次追加选择，最后一次性发送。
 */
(function (window, $) {
    var MAX = 9;
    var EXTS = {
        image: ['jpg', 'jpeg', 'png', 'gif'],
        video: ['mp4', 'mov', 'avi', 'mkv', 'flv', 'wmv', 'webm']
    };
    var current = null;

    function getExt(name) {
        var i = name.lastIndexOf('.');
        return i < 0 ? '' : name.substring(i + 1).toLowerCase();
    }

    function tip(msg) {
        if (window.layer) {
            layer.msg(msg, {icon: 2});
        } else {
            alert(msg);
        }
    }

    function esc(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function filterFiles(files, type) {
        var valid = [], bad = [];
        $.each(files, function (i, f) {
            if (EXTS[type].indexOf(getExt(f.name)) >= 0) {
                valid.push(f);
            } else {
                bad.push(f.name);
            }
        });
        return {valid: valid, bad: bad};
    }

    /**
     * 入口：file input change 后调用
     */
    function pick(input, opts) {
        var files = [];
        if (input.files) {
            for (var i = 0; i < input.files.length; i++) files.push(input.files[i]);
        }
        var clearInput = function () { input.value = ''; };

        if (!files.length) return;

        // 预览面板已打开：把新选的文件追加进去（手机相册不支持多选时的“继续添加”路径）
        if (current) {
            if (current.sending) {
                tip('文件正在发送中，请稍候');
                clearInput();
                return;
            }
            var r0 = filterFiles(files, current.opts.type);
            if (r0.bad.length) {
                tip('已忽略 ' + r0.bad.length + ' 个不支持格式的文件');
            }
            var add = r0.valid;
            var remain = MAX - current.items.length;
            if (add.length > remain) {
                tip('一次最多只能选择 ' + MAX + ' 个文件');
                add = add.slice(0, Math.max(remain, 0));
            }
            $.each(add, function (i, f) {
                current.items.push({file: f, url: URL.createObjectURL(f), state: 'wait'});
            });
            clearInput();
            current.refresh();
            return;
        }

        var r = filterFiles(files, opts.type);
        if (r.bad.length) {
            tip('已忽略 ' + r.bad.length + ' 个不支持格式的文件');
        }
        var valid = r.valid;
        if (valid.length > MAX) {
            tip('一次最多只能选择 ' + MAX + ' 个文件');
            valid = valid.slice(0, MAX);
        }
        if (!valid.length) {
            clearInput();
            return;
        }

        openPanel(valid, opts, input, clearInput);
    }

    function openPanel(files, opts, input, clearInput) {
        var items = [];
        $.each(files, function (i, f) {
            items.push({file: f, url: URL.createObjectURL(f), state: 'wait'});
        });

        var isVideo = opts.type === 'video';
        var sending = false;

        var html = '';
        html += '<div id="ms_panel" style="position:fixed;left:0;right:0;bottom:0;z-index:999999;background:#fff;box-shadow:0 -2px 12px rgba(0,0,0,.18);padding:10px 12px;font-size:14px;">';
        html += '<div id="ms_list" style="display:flex;flex-wrap:wrap;max-height:170px;overflow:auto;"></div>';
        html += '<div style="margin-top:8px;display:flex;align-items:center;justify-content:space-between;">';
        html += '<span id="ms_status" style="color:#999;flex:1;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;"></span>';
        html += '<span style="flex:none;">';
        html += '<button type="button" id="ms_send" style="height:32px;padding:0 18px;border:none;background:#1e9fff;color:#fff;border-radius:4px;cursor:pointer;">发送</button>';
        html += '<button type="button" id="ms_cancel" style="height:32px;padding:0 14px;border:1px solid #ddd;background:#fff;border-radius:4px;cursor:pointer;margin-left:8px;">取消</button>';
        html += '</span>';
        html += '</div></div>';
        $('body').append(html);

        current = {
            items: items,
            opts: opts,
            input: input,
            sending: false,
            refresh: function () {
                renderList();
                if (!sending) {
                    setStatus('已选择 ' + items.length + ' 个文件');
                }
            }
        };

        function renderList() {
            var $list = $('#ms_list').empty();
            $.each(items, function (i, it) {
                var inner;
                if (isVideo) {
                    inner = '<div style="width:100%;height:100%;background:#333;color:#fff;border-radius:4px;display:flex;flex-direction:column;align-items:center;justify-content:center;font-size:12px;padding:2px;box-sizing:border-box;">'
                        + '<span style="font-size:20px;">&#9658;</span>'
                        + '<span style="max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + esc(it.file.name) + '</span></div>';
                } else {
                    inner = '<img src="' + it.url + '" style="width:100%;height:100%;object-fit:cover;border-radius:4px;display:block;">';
                }
                var stateStyle = '';
                if (it.state === 'ok') stateStyle = 'opacity:.45;';
                if (it.state === 'fail') stateStyle = 'outline:2px solid #f00;';
                var $item = $('<div class="ms_item" data-i="' + i + '" style="position:relative;width:64px;height:64px;margin:4px;flex:none;' + stateStyle + '"></div>');
                $item.append(inner);
                if (it.state === 'wait' && !sending) {
                    $item.append('<span class="ms_del" data-i="' + i + '" style="position:absolute;top:-6px;right:-6px;width:18px;height:18px;background:#ff5722;color:#fff;border-radius:50%;text-align:center;line-height:16px;font-size:14px;cursor:pointer;">&times;</span>');
                } else if (it.state === 'ok') {
                    $item.append('<span style="position:absolute;right:2px;bottom:2px;color:#090;font-size:16px;">&#10004;</span>');
                }
                $list.append($item);
            });
            // “继续添加”按钮：手机相册不支持多选时，可多次追加
            if (!sending && items.length < MAX) {
                $list.append('<div class="ms_add" style="width:64px;height:64px;margin:4px;flex:none;border:1px dashed #bbb;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#999;font-size:28px;cursor:pointer;">+</div>');
            }
        }

        function setStatus(text) {
            $('#ms_status').text(text);
        }

        function closePanel() {
            $.each(items, function (i, it) {
                if (it.url) {
                    try { URL.revokeObjectURL(it.url); } catch (e) {}
                }
            });
            $('#ms_panel').remove();
            $(document).off('click.msDel').off('click.msAdd');
            current = null;
        }

        renderList();
        setStatus('已选择 ' + items.length + ' 个文件');

        $(document).off('click.msDel').on('click.msDel', '.ms_del', function () {
            var idx = parseInt($(this).attr('data-i'), 10);
            var it = items[idx];
            if (it && it.url) {
                try { URL.revokeObjectURL(it.url); } catch (e) {}
            }
            items.splice(idx, 1);
            if (!items.length) {
                closePanel();
                clearInput();
                return;
            }
            renderList();
            setStatus('已选择 ' + items.length + ' 个文件');
        });

        $(document).off('click.msAdd').on('click.msAdd', '.ms_add', function () {
            if (current && !current.sending) {
                current.input.click();
            }
        });

        $('#ms_cancel').on('click', function () {
            closePanel();
            clearInput();
        });

        $('#ms_send').on('click', function () {
            sending = true;
            current.sending = true;
            var $btn = $(this);
            $btn.prop('disabled', true).css('opacity', .6);
            $('#ms_cancel').prop('disabled', true).css('opacity', .6);
            renderList();
            var total = items.length, done = 0, okCount = 0, failCount = 0;

            function next() {
                if (done >= total) {
                    var msg = okCount + ' 个发送成功';
                    if (failCount > 0) msg += '，' + failCount + ' 个发送失败';
                    closePanel();
                    clearInput();
                    if (failCount > 0) tip(msg);
                    return;
                }
                var idx = done;
                var it = items[idx];
                setStatus('正在发送 (' + (done + 1) + '/' + total + ')');
                opts.send(it.file, function () {
                    it.state = 'ok';
                    okCount++;
                    done++;
                    renderList();
                    next();
                }, function (errMsg) {
                    it.state = 'fail';
                    failCount++;
                    done++;
                    renderList();
                    if (errMsg) tip(errMsg);
                    next();
                });
            }

            next();
        });
    }

    /**
     * 通用单文件上传（FormData）。
     * opts: {
     *   url: 上传接口地址,
     *   fieldName: 文件字段名（如 upload / video）,
     *   data: 额外表单参数对象,
     *   onOk: function(res) 上传成功（res.code==0）,
     *   onFail: function(msg) 上传失败
     * }
     */
    function uploadFile(file, opts) {
        var fd = new FormData();
        fd.append(opts.fieldName, file);
        if (opts.data) {
            for (var k in opts.data) {
                if (Object.prototype.hasOwnProperty.call(opts.data, k)) {
                    fd.append(k, opts.data[k]);
                }
            }
        }
        $.ajax({
            url: opts.url,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                if (res && res.code == 0) {
                    opts.onOk(res);
                } else {
                    opts.onFail(res && res.msg ? res.msg : '上传失败');
                }
            },
            error: function () {
                opts.onFail('上传失败，请重试');
            }
        });
    }

    window.MultiSend = {
        pick: pick,
        uploadFile: uploadFile,
        MAX: MAX
    };
})(window, jQuery);

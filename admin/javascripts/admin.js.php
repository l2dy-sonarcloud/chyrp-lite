<?php
    /**
     * File: admin.js.php
     * JavaScript for core functionality and extensions.
     */
    if (!defined('CHYRP_VERSION'))
        exit;
?>
'use strict';

$(function() {
    toggle_all();
    validate_pattern();
    validate_email();
    validate_url();
    validate_passwords();
    confirm_submit();
    solo_submit();
    test_uploads();
    Help.init();
    Write.init();
    Settings.init();
});
// Adds a master toggle to forms that have multiple checkboxes.
function toggle_all(
) {
    $("form[data-toggler]").each(
        function() {
            var all_on = true;
            var target = $(this);
            var parent = $("#" + $(this).attr("data-toggler"));
            var slaves = target.find(":checkbox");
            var master = Date.now().toString(16);

            slaves.each(
                function() {
                    return all_on = $(this).prop("checked");
                }
            );

            slaves.click(
                function(e) {
                    slaves.each(
                        function() {
                            return all_on = $(this).prop("checked");
                        }
                    );

                    $("#" + master).prop("checked", all_on);
                }
            );

            parent.append(
                [$("<label>", {
                    "for": master
                }).text('<?php esce(__("Toggle All", "admin")); ?>'),
                $("<input>", {
                    "type": "checkbox",
                    "name": "toggle",
                    "id": master,
                    "class": "checkbox",
                    "aria-label": '<?php esce(__("Toggle All", "admin")); ?>'
                }).prop("checked", all_on).click(
                    function(e) {
                        slaves.prop("checked", $(this).prop("checked"));
                    }
                )]
            );
        }
    );
}
// Validates input patterns.
function validate_pattern(
) {
    $("input[pattern]").keyup(
        function(e) {
            var value = $(this).val();
            var regexp = new RegExp($(this).attr("pattern"));

            if (regexp.test(value))
                $(this).removeClass("error");
            else
                $(this).addClass("error");
        }
    );
}
// Validates email fields.
function validate_email(
) {
    $("input[type='email']").keyup(
        function(e) {
            var text = $(this).val();

            if (text != "" && !isEmail(text))
                $(this).addClass("error");
            else
                $(this).removeClass("error");
        }
    );
}
// Validates URL fields.
function validate_url(
) {
    $("input[type='url']").keyup(
        function(e) {
            var text = $(this).val();

            if (text != "" && !isURL(text))
                $(this).addClass("error");
            else
                $(this).removeClass("error");
        }
    );

    $("input[type='url']").on(
        "change",
        function(e) {
            var text = $(this).val();

            if (isURL(text))
                $(this).val(addScheme(text));
        }
    );
}
// Tests the strength of #password1 and compares #password1 to #password2.
function validate_passwords(
) {
    var passwords = $("input[type='password']").filter(
        function(index) {
            var id = $(this).attr("id");
            return (!!id) ? id.match(/password[1-2]$/) : false ;
        }
    );

    passwords.first().keyup(
        function(e) {
            var password = $(this).val();

            if (passwordStrength(password) > 99)
                $(this).addClass("strong");
            else
                $(this).removeClass("strong");
        }
    );

    passwords.keyup(
        function(e) {
            var password1 = passwords.first().val();
            var password2 = passwords.last().val();

            if (password1 != "" && password1 != password2)
                passwords.last().addClass("error");
            else
                passwords.last().removeClass("error");
        }
    );

    passwords.parents("form").on(
        "submit.passwords",
        function(e) {
            var password1 = passwords.first().val();
            var password2 = passwords.last().val();

            if (password1 != password2) {
                e.preventDefault();
                alert('<?php esce(__("Passwords do not match.")); ?>');
            }
        }
    );
}
// Asks the user to confirm form submission.
function confirm_submit(
) {
    $("form[data-confirm]").on(
        "submit.confirm",
        function(e) {
            var text = $(this).attr("data-confirm") ||
                       '<?php esce(__("Are you sure you want to proceed?", "admin")); ?>' ;

            if (!confirm(text.replace(/<[^>]+>/g, "")))
                e.preventDefault();
        }
    );

    $("button[data-confirm]").on(
        "click.confirm",
        function(e) {
            var text = $(this).attr("data-confirm") ||
                       '<?php esce(__("Are you sure you want to proceed?", "admin")); ?>' ;

            if (!confirm(text.replace(/<[^>]+>/g, "")))
                e.preventDefault();
        }
    );
}
// Prevents forms being submitted multiple times in a short interval.
function solo_submit(
) {
    $("form").on(
        "submit.solo",
        function(e) {
            var last = $(this).attr("data-submitted") || 0 ;
            var when = Date.now();

            if ((when - last) < 5000) {
                e.preventDefault();
                console.log("Form submission blocked for 5 secs.");
            } else {
                $(this).attr("data-submitted", when);
            }
        }
    );
}
function test_uploads(
) {
    $("input[type='file']:not(.toolbar)").on(
        "change.uploads",
        function(e) {
            if (!!e.target.files && e.target.files.length > 0) {
                for (var i = 0; i < e.target.files.length; i++) {
                    var file = e.target.files[i];

                    if (file.size > Uploads.limit) {
                        e.target.value = null;
                        alert(Uploads.messages.size_err);
                        break;
                    }
                }
            }
        }
    );
}
var Route = {
    action: '<?php esce($route->action); ?>',
    request: '<?php esce($route->request); ?>'
}
var Visitor = {
    id: <?php esce($visitor->id); ?>,
    token: '<?php esce(authenticate()); ?>'
}
var Site = {
    url: '<?php esce($config->url); ?>',
    chyrp_url: '<?php esce($config->chyrp_url); ?>',
    ajax_url: '<?php esce(unfix(url('/', 'AjaxController'))); ?>'
}
var Oops = {
    message: '<?php esce(__("Oops! Something went wrong on this web page.")); ?>',
    count: 0
}
var Uploads = {
    limit: <?php esce(intval($config->uploads_limit * 1000000)); ?>,
    messages: {
        send_err: '<?php esce(__("File upload failed.", "admin")); ?>',
        type_err: '<?php esce(__("File type not supported.", "admin")); ?>',
        size_err: '<?php esce(_f("Maximum file size: %d Megabytes.", $config->uploads_limit, "admin")); ?>'
    },
    active: 0,
    send: function(
        file,
        doneCallback,
        failCallback,
        alwaysCallback
    ) {
        Uploads.active++;
        var form = new FormData();

        form.set("action", "file_upload");
        form.set("hash", Visitor.token);
        form.set("file", file, file.name);

        $.ajax({
            type: "POST",
            url: Site.ajax_url,
            data: form,
            processData: false,
            contentType: false,
            dataType: "json",
        }).done(
            function(response) {
                doneCallback(response);
            }
        ).fail(
            function(response) {
                Oops.count++;
                failCallback(response);
            }
        ).always(
            function(response) {
                Uploads.active--;
                alwaysCallback(response);
            }
        );
    },
    show: function(
        search,
        filter,
        clickCallback,
        failCallback,
        alwaysCallback
    ) {
        if (Uploads.active)
            return;

        Uploads.active++;

        $.post(
            Site.ajax_url,
            {
                action: "uploads_modal",
                hash: Visitor.token,
                search: search,
                filter: filter
            },
            function(data) {
                $(
                    "<div>",
                    {
                        "role": "dialog",
                        "aria-label": '<?php esce(__("Modal window", "admin")); ?>'
                    }
                ).addClass(
                    "iframe_background"
                ).append(
                    [
                        $(
                            "<div>",
                            {
                                "title": '<?php esce(__("Uploads", "admin")); ?>',
                            }
                        ).addClass(
                            "iframe_foreground"
                        ).on(
                            "click",
                            "a",
                            function(e) {
                                e.preventDefault();
                                var target = $(e.target);
                                var file = {
                                    href: target.attr("href"),
                                    name: target.attr("data-name"),
                                    type: target.attr("data-type"),
                                    size: Number(target.attr("data-size"))
                                };
                                clickCallback(file);
                                $(this).parents(".iframe_background").remove();
                            }
                        ).append(data),
                        $(
                            "<a>",
                            {
                                "href": "#",
                                "role": "button",
                                "accesskey": "x",
                                "aria-label": '<?php esce(__("Close", "admin")); ?>'
                            }
                        ).addClass(
                            "iframe_close_gadget"
                        ).click(
                            function(e) {
                                e.preventDefault();
                                $(this).parent().remove();
                            }
                        ).append(
                            '<?php esce(icon_svg("close.svg")); ?>'
                        )
                    ]
                ).click(
                    function(e) {
                        if (e.target === e.currentTarget)
                            $(this).remove();
                    }
                ).insertAfter("#content").children("a.iframe_close_gadget").focus();
            },
            "html"
        ).fail(
            function(response) {
                Oops.count++;
                failCallback(response);
            }
        ).always(
            function(response) {
                Uploads.active--;
                alwaysCallback(response);
            }
        );
    }
}
var FileInput = {
    mutate: function(
        target
    ) {
        if (target.attr("type") === "file") {
            target.attr(
                "type",
                "text"
            ).attr(
                "name",
                target.attr("name").replace(/\[\]$/, "")
            ).val(
                target.attr("data-file_list") || ""
            ).attr(
                "pattern",
                (typeof target.attr("multiple") === "undefined") ?
                    "^[a-z0-9\\-\\.]*$" :
                    "^([a-z0-9\\-\\.](, *)?)*$"
            ).addClass(
                "text"
            ).keyup(
                function(e) {
                    var value = $(this).val();
                    var regexp = new RegExp($(this).attr("pattern"));

                    if (regexp.test(value))
                        $(this).removeClass("error");
                    else
                        $(this).addClass("error");
                }
            );
        }

        return target;
    },
    insert: function(
        target,
        filename
    ) {
        if (typeof target.attr("multiple") === "undefined") {
            target.val(filename);
        } else {
            var regexp = new RegExp("(, *|^)" + escapeRegExp(filename) + "(, *|$)", "g");

            if (!regexp.test(target.val()))
                target.val(
                    target.val().replace(/([^,])(, *)?$/, "$1, ") + filename
                );
        }

        return target;
    }
}
var Help = {
    init: function(
    ) {
        $(".help").on(
            "click",
            function(e) {
                e.preventDefault();
                Help.show($(this).attr("href"));
            }
        );
    },
    show: function(
        href
    ) {
        $("<div>", {
            "role": "dialog",
            "aria-label": '<?php esce(__("Modal window", "admin")); ?>'
        }).addClass(
            "iframe_background"
        ).append(
            [
                $(
                    "<iframe>",
                    {
                        "src": href,
                        "title": '<?php esce(__("Help content", "admin")); ?>',
                        "sandbox": "allow-same-origin allow-popups allow-popups-to-escape-sandbox"
                    }
                ).addClass(
                    "iframe_foreground"
                ).loader().on(
                    "load",
                    function() {
                        $(this).loader(true);
                    }
                ),
                $(
                    "<a>",
                    {
                        "href": "#",
                        "role": "button",
                        "accesskey": "x",
                        "aria-label": '<?php esce(__("Close", "admin")); ?>'
                    }
                ).addClass(
                    "iframe_close_gadget"
                ).click(
                    function(e) {
                        e.preventDefault();
                        $(this).parent().remove();
                    }
                ).append(
                    '<?php esce(icon_svg("close.svg")); ?>'
                )
            ]
        ).click(
            function(e) {
                if (e.target === e.currentTarget)
                    $(this).remove();
            }
        ).insertAfter("#content").children("a.iframe_close_gadget").focus();
    }
}
var Write = {
    init: function(
    ) {
        // Insert toolbar buttons for file inputs.
        $("#write_form .file_toolbar, #edit_form .file_toolbar").each(
            function() {
                var toolbar = $(this);
                var target = $("#" + toolbar.attr("id").replace("_toolbar", ""));
                var tray = $("#" + target.attr("id") + "_tray");

                toolbar.append(
                    $(
                        "<button>",
                        {
                            "type": "button",
                            "title": '<?php esce(__("Edit", "admin")); ?>',
                            "aria-label": '<?php esce(__("Edit", "admin")); ?>'
                        }
                    ).addClass(
                        "emblem toolbar"
                    ).click(
                        function(e) {
                            FileInput.mutate(target).focus();
                        }
                    ).append(
                        '<?php esce(icon_svg("edit.svg")); ?>'
                    )
                );

                // Insert button to open the uploads modal.
                if (<?php esce($visitor->group->can("view_upload")); ?>) {
                    toolbar.append(
                        $(
                            "<button>",
                            {
                                "type": "button",
                                "title": '<?php esce(__("Insert", "admin")); ?>',
                                "aria-label": '<?php esce(__("Insert", "admin")); ?>'
                            }
                        ).addClass(
                            "emblem toolbar"
                        ).click(
                            function(e) {
                                toolbar.loader();

                                var search = "" ;
                                var filter = target.attr("accept") || "" ;

                                Uploads.show(
                                    search,
                                    filter,
                                    function(file) {
                                        FileInput.mutate(target);
                                        FileInput.insert(
                                            target,
                                            file.name
                                        );
                                    },
                                    function(response) {
                                        alert(Oops.message);
                                    },
                                    function(response) {
                                        toolbar.loader(true);
                                    }
                                );
                            }
                        ).append(
                            '<?php esce(icon_svg("storage.svg")); ?>'
                        )
                    );
                }

                // Do not continue if the target has <data-no_uploads>.
                if (typeof target.attr("data-no_uploads") !== "undefined")
                    return;

                // Insert toolbar button for uploads.
                if (<?php esce($visitor->group->can("add_upload")); ?>) {
                    $(
                        "<label>",
                        {
                            "role": "button",
                            "title": '<?php esce(__("Upload", "admin")); ?>',
                            "aria-label": '<?php esce(__("Upload", "admin")); ?>'
                        }
                    ).addClass(
                        "emblem toolbar"
                    ).append(
                        [
                            $(
                                "<input>",
                                {
                                    "name": toolbar.attr("id") + "_upload",
                                    "type": "file"
                                }
                            ).addClass(
                                "toolbar hidden"
                            ).change(
                                function(e) {
                                    if (!!e.target.files && e.target.files.length > 0) {
                                        var file = e.target.files[0];

                                        // Reject files too large to upload.
                                        if (file.size > Uploads.limit) {
                                            e.target.value = null;
                                            alert(Uploads.messages.size_err);
                                            return;
                                        }

                                        toolbar.loader();

                                        // Upload the file.
                                        Uploads.send(
                                            file,
                                            function(response) {
                                                FileInput.mutate(target);
                                                FileInput.insert(
                                                    target,
                                                    response.data.name
                                                );
                                            },
                                            function(response) {
                                                alert(Uploads.messages.send_err);
                                            },
                                            function(response) {
                                                toolbar.loader(true);
                                                e.target.value = null;
                                            }
                                        );
                                    }
                                }
                            ),
                            $('<?php esce(icon_svg("upload.svg")); ?>')
                        ]
                    ).appendTo(toolbar);
                }
            }
        );

        // Insert toolbar buttons for textareas.
        $("#write_form .text_block_toolbar, #edit_form .text_block_toolbar").each(
            function() {
                var toolbar = $(this);
                var target = $("#" + toolbar.attr("id").replace("_toolbar", ""));
                var tray = $("#" + target.attr("id") + "_tray");

                toolbar.append(
                    $(
                        "<button>",
                        {
                            "type": "button",
                            "title": '<?php esce(__("Heading", "admin")); ?>',
                            "aria-label": '<?php esce(__("Heading", "admin")); ?>'
                        }
                    ).addClass(
                        "emblem toolbar"
                    ).click(
                        function(e) {
                            Write.formatting(target, "h3");
                        }
                    ).append(
                        '<?php esce(icon_svg("heading.svg")); ?>'
                    )
                );

                toolbar.append(
                    $(
                        "<button>",
                        {
                            "type": "button",
                            "title": '<?php esce(__("Strong", "admin")); ?>',
                            "aria-label": '<?php esce(__("Strong", "admin")); ?>'
                        }
                    ).addClass(
                        "emblem toolbar"
                    ).click(
                        function(e) {
                            Write.formatting(target, "strong");
                        }
                    ).append(
                        '<?php esce(icon_svg("bold.svg")); ?>'
                    )
                );

                toolbar.append(
                    $(
                        "<button>",
                        {
                            "type": "button",
                            "title": '<?php esce(__("Emphasis", "admin")); ?>',
                            "aria-label": '<?php esce(__("Emphasis", "admin")); ?>'
                        }
                    ).addClass(
                        "emblem toolbar"
                    ).click(
                        function(e) {
                            Write.formatting(target, "em");
                        }
                    ).append(
                        '<?php esce(icon_svg("italic.svg")); ?>'
                    )
                );

                toolbar.append(
                    $(
                        "<button>",
                        {
                            "type": "button",
                            "title": '<?php esce(__("Strikethrough", "admin")); ?>',
                            "aria-label": '<?php esce(__("Strikethrough", "admin")); ?>'
                        }
                    ).addClass(
                        "emblem toolbar"
                    ).click(
                        function(e) {
                            Write.formatting(target, "del");
                        }
                    ).append(
                        '<?php esce(icon_svg("strikethrough.svg")); ?>'
                    )
                );

                toolbar.append(
                    $(
                        "<button>",
                        {
                            "type": "button",
                            "title": '<?php esce(__("Code", "admin")); ?>',
                            "aria-label": '<?php esce(__("Code", "admin")); ?>'
                        }
                    ).addClass(
                        "emblem toolbar"
                    ).click(
                        function(e) {
                            Write.formatting(target, "code");
                        }
                    ).append(
                        '<?php esce(icon_svg("code.svg")); ?>'
                    )
                );

                toolbar.append(
                    $(
                        "<button>",
                        {
                            "type": "button",
                            "title": '<?php esce(__("Hyperlink", "admin")); ?>',
                            "aria-label": '<?php esce(__("Hyperlink", "admin")); ?>'
                        }
                    ).addClass(
                        "emblem toolbar"
                    ).click(
                        function(e) {
                            Write.formatting(target, "hyperlink");
                        }
                    ).append(
                        '<?php esce(icon_svg("link.svg")); ?>'
                    )
                );

                toolbar.append(
                    $(
                        "<button>",
                        {
                            "type": "button",
                            "title": '<?php esce(__("Horizontal rule", "admin")); ?>',
                            "aria-label": '<?php esce(__("Horizontal rule", "admin")); ?>'
                        }
                    ).addClass(
                        "emblem toolbar"
                    ).click(
                        function(e) {
                            Write.formatting(target, "hr");
                        }
                    ).append(
                        '<?php esce(icon_svg("hr.svg")); ?>'
                    )
                );

                toolbar.append(
                    $(
                        "<button>",
                        {
                            "type": "button",
                            "title": '<?php esce(__("Blockquote", "admin")); ?>',
                            "aria-label": '<?php esce(__("Blockquote", "admin")); ?>'
                        }
                    ).addClass(
                        "emblem toolbar"
                    ).click(
                        function(e) {
                            Write.formatting(target, "blockquote");
                        }
                    ).append(
                        '<?php esce(icon_svg("quote.svg")); ?>'
                    )
                );

                toolbar.append(
                    $(
                        "<button>",
                        {
                            "type": "button",
                            "title": '<?php esce(__("Image", "admin")); ?>',
                            "aria-label": '<?php esce(__("Image", "admin")); ?>'
                        }
                    ).addClass(
                        "emblem toolbar"
                    ).click(
                        function(e) {
                            Write.formatting(target, "img");
                        }
                    ).append(
                        '<?php esce(icon_svg("image.svg")); ?>'
                    )
                );

                // Insert button to open the uploads modal.
                if (<?php esce($visitor->group->can("view_upload")); ?>) {
                    toolbar.append(
                        $(
                            "<button>",
                            {
                                "type": "button",
                                "title": '<?php esce(__("Insert", "admin")); ?>',
                                "aria-label": '<?php esce(__("Insert", "admin")); ?>'
                            }
                        ).addClass(
                            "emblem toolbar"
                        ).click(
                            function(e) {
                                toolbar.loader();

                                var search = target.attr("data-uploads_search") || "" ;
                                var filter = target.attr("data-uploads_filter") || "" ;

                                Uploads.show(
                                    search,
                                    filter,
                                    function(file) {
                                        Write.formatting(
                                            target,
                                            "insert",
                                            file.href
                                        );
                                    },
                                    function(response) {
                                        alert(Oops.message);
                                    },
                                    function(response) {
                                        toolbar.loader(true);
                                    }
                                );
                            }
                        ).append(
                            '<?php esce(icon_svg("storage.svg")); ?>'
                        )
                    );
                }

                // Do not continue if the target has <data-no_uploads>.
                if (typeof target.attr("data-no_uploads") !== "undefined")
                    return;

                // Insert toolbar button for uploads.
                if (<?php esce($visitor->group->can("add_upload")); ?>) {
                    $(
                        "<label>",
                        {
                            "role": "button",
                            "title": '<?php esce(__("Upload", "admin")); ?>',
                            "aria-label": '<?php esce(__("Upload", "admin")); ?>'
                        }
                    ).addClass(
                        "emblem toolbar"
                    ).append(
                        [
                            $(
                                "<input>",
                                {
                                    "name": toolbar.attr("id") + "_upload",
                                    "type": "file"
                                }
                            ).addClass(
                                "toolbar hidden"
                            ).change(
                                function(e) {
                                    if (!!e.target.files && e.target.files.length > 0) {
                                        var file = e.target.files[0];

                                        // Reject files too large to upload.
                                        if (file.size > Uploads.limit) {
                                            e.target.value = null;
                                            tray.html(Uploads.messages.size_err);
                                            return;
                                        }

                                        toolbar.loader();

                                        // Upload the file.
                                        Uploads.send(
                                            file,
                                            function(response) {
                                                Write.formatting(
                                                    target,
                                                    "insert",
                                                    response.data.href
                                                );
                                            },
                                            function(response) {
                                                alert(Uploads.messages.send_err);
                                            },
                                            function(response) {
                                                toolbar.loader(true);
                                                e.target.value = null;
                                            }
                                        );
                                    }
                                }
                            ),
                            $('<?php esce(icon_svg("upload.svg")); ?>')
                        ]
                    ).appendTo(toolbar);
                }
            }
        );

        // Insert buttons for ajax post/page previews.
        if (<?php esce($theme->file_exists("content".DIR."preview")); ?>) {
            $("*[data-preview]").each(
                function() {
                    var target = $(this);
                    var form = target.closest("form");

                    var action = form.attr("action").match(
                        /(add|update)_([a-zA-Z0-9]+)\/?$/
                    );

                    if (action) {
                        $("#" + target.attr("id") + "_toolbar").append(
                            $(
                                "<button>",
                                {
                                    "type": "button",
                                    "title": '<?php esce(__("Preview", "admin")); ?>',
                                    "aria-label": '<?php esce(__("Preview", "admin")); ?>'
                                }
                            ).addClass(
                                "emblem toolbar"
                            ).click(
                                function(e) {
                                    var content = target.val();
                                    var field = target.attr("name");
                                    var context = target.attr("data-preview");

                                    if (content == "") {
                                        target.focus();
                                    } else {
                                        Write.show(
                                            "preview_" + action[2],
                                            context,
                                            field,
                                            content
                                        );
                                    }
                                }
                            ).append(
                                '<?php esce(icon_svg("view.svg")); ?>'
                            )
                        );
                    }
                }
            );
        }

        // Support drag-and-drop uploads.
        if (<?php esce($visitor->group->can("add_upload")); ?>) {
            $("#write_form textarea, #edit_form textarea").each(
                function() {
                    var target = $(this);

                    // Do not continue if the textarea has <data-no_uploads>.
                    if (typeof target.attr("data-no_uploads") !== "undefined")
                        return;

                    target.on("dragover", Write.dragover).
                           on("dragenter", Write.dragenter).
                           on("dragleave", Write.dragleave).
                           on("drop", Write.drop);
                }
            );
        }

        // Add a word counter to textarea elements.
        $("#write_form textarea, #edit_form textarea").each(
            function() {
                var target = $(this);
                var tray = $("#" + target.attr("id") + "_tray");
                var regex = /\p{White_Space}+/gu;
                var label = '<?php esce(__("Words:", "admin")); ?>';

                target.on(
                    "input",
                    function(e) {
                        var words = target.val();
                        var count = words.trim().match(regex);
                        var total = !!count ? count.length + 1 : 1 ;

                        if (total == 1 && words.match(/^\p{White_Space}*$/gu))
                            total = 0;

                        tray.html(label + " " + total);
                    }
                );

                target.trigger("input");
            }
        );

        // Remember unsaved text entered in the primary text input and textarea.
        if (!!sessionStorage) {
            var prefix = "user_" + Visitor.id;

            $("#write_form .main_options input[type='text']").first().val(
                function() {
                    try {
                        return sessionStorage.getItem(prefix + "_write_title");
                    } catch(err) {
                        console.log("Caught Exception: Window.sessionStorage.getItem()");
                        return null;
                    }
                }
            ).trigger("input").on(
                "change",
                function(e) {
                    try {
                        sessionStorage.setItem(prefix + "_write_title", $(this).val());
                    } catch(err) {
                        console.log("Caught Exception: Window.sessionStorage.setItem()");
                    }
                }
            );

            $("#write_form .main_options textarea").first().val(
                function(index, value) {
                    try {
                        return sessionStorage.getItem(prefix + "_write_body");
                    } catch(err) {
                        console.log("Caught Exception: Window.sessionStorage.getItem()");
                        return null;
                    }
                }
            ).trigger("input").on(
                "change",
                function(e) {
                    try {
                        sessionStorage.setItem(prefix + "_write_body", $(this).val());
                    } catch(err) {
                        console.log("Caught Exception: Window.sessionStorage.setItem()");
                    }
                }
            );

            $("#write_form").on(
                "submit.sessionStorage",
                function(e) {
                    try {
                        sessionStorage.removeItem(prefix + "_write_title");
                        sessionStorage.removeItem(prefix + "_write_body");
                    } catch(err) {
                        console.log("Caught Exception: Window.sessionStorage.removeItem()");
                    }
                }
            );
        }
    },
    dragenter: function(
        e
    ) {
        $(e.target).addClass("drag_highlight");
    },
    dragleave: function(
        e
    ) {
        $(e.target).removeClass("drag_highlight");
    },
    dragover: function(
        e
    ) {
        e.preventDefault();
    },
    drop: function(
        e
    ) {
        // Process drag-and-drop file uploads.
        e.stopPropagation();
        e.preventDefault();
        var dt = e.originalEvent.dataTransfer;

        if (!!dt && !!dt.files && dt.files.length > 0) {
            var file = dt.files[0];
            var form = new FormData();
            var target = $(e.target);
            var tray = $("#" + $(e.target).attr("id") + "_tray");

            // Reject files too large to upload.
            if (file.size > Uploads.limit) {
                alert(Uploads.messages.size_err);
                return;
            }

            target.loader();

            // Upload the file.
            Uploads.send(
                file,
                function(response) {
                    Write.formatting(
                        target,
                        "insert",
                        response.data.href
                    );
                },
                function(response) {
                    alert(Uploads.messages.send_err);
                },
                function(response) {
                    target.loader(true);
                    target.removeClass("drag_highlight");
                }
            );
        }
    },
    formatting: function(
        target,
        effect,
        fragment = ""
    ) {
        var markdown = (typeof target.attr("data-markdown") !== "undefined");
        var opening = "";
        var closing = "";
        var after = "";
        var start = target[0].selectionStart;
        var end = target[0].selectionEnd;
        var selection = target.val().substring(start, end);

        // Test for a trailing space caused by double-click word selection.
        if (selection.length > 0) {
            if (selection.slice(-1) == " ") {
                after = " ";
                selection = selection.substring(0, selection.length - 1);
            }
        }

        switch (effect) {
            case 'strong':
                opening = (markdown) ?
                    "**" :
                    '<strong>' ;

                closing = (markdown) ?
                    "**" :
                    '</strong>' ;

                if (selection == "")
                    selection = " ";

                break;

            case 'em':
                opening = (markdown) ?
                    "*" :
                    '<em>' ;

                closing = (markdown) ?
                    "*" :
                    '</em>' ;

                if (selection == "")
                    selection = " ";

                break;

            case 'code':
                opening = (markdown) ?
                    "`" :
                    '<code>' ;

                closing = (markdown) ?
                    "`" :
                    '</code>' ;

                if (selection == "")
                    selection = " ";

                break;

            case 'h3':
                opening = (markdown) ?
                    "### " :
                    '<h3>' ;

                closing = (markdown) ?
                    "" :
                    '</h3>' ;

                break;

            case 'del':
                opening = (markdown) ?
                    "~~" :
                    '<del>' ;

                closing = (markdown) ?
                    "~~" :
                    '</del>' ;

                if (selection == "")
                    selection = " ";

                break;

            case 'mark':
                opening = (markdown) ?
                    "==" :
                    '<mark>' ;

                closing = (markdown) ?
                    "==" :
                    '</mark>' ;

                if (selection == "")
                    selection = " ";

                break;

            case 'hyperlink':
                if (isURL(selection)) {
                    if (fragment) {
                        selection = fragment;
                        break;
                    }

                    opening = (markdown) ?
                        "[](" :
                        '<a href="' ;

                    closing = (markdown) ?
                        ")" :
                        '"></a>' ;
                } else {
                    opening = (markdown) ?
                        "[" :
                        '<a href="' + fragment + '">' ;

                    closing = (markdown) ?
                        "](" + fragment + ")" :
                        '</a>' ;
                }

                break;

            case 'hr':
                closing = (markdown) ?
                    "\n***\n" :
                    '\n<hr>\n' ;

                break;

            case 'blockquote':
                opening = (markdown) ?
                    "\n>>>\n" :
                    '\n<blockquote>\n' ;

                closing = (markdown) ?
                    "\n>>>\n" :
                    '\n</blockquote>\n' ;

                break;

            case 'img':
                if (isURL(selection)) {
                    if (fragment) {
                        selection = fragment;
                        break;
                    }

                    opening = (markdown) ?
                        "![](" :
                        '<img alt="" src="' ;

                    closing = (markdown) ?
                        ")" :
                        '">' ;
                } else {
                    opening = (markdown) ?
                        "![" :
                        '<img alt="' ;

                    closing = (markdown) ?
                        "](" + fragment + ")" :
                        '" src="' + fragment + '">' ;
                }

                break;

            case 'insert':
                selection = fragment;
                break;
        }

        var text = opening + selection + closing + after;
        target[0].setRangeText(text);
        $(target).focus().trigger("input").trigger("change");
    },
    show: function(
        action,
        context,
        field,
        content
    ) {
        var uid = window.generateUUIDv4?.() ?? Date.now().toString(16) ;

        // Build a form targeting a named iframe.
        var form = $(
            "<form>",
            {
                "id": uid,
                "action": Site.ajax_url,
                "method": "post",
                "accept-charset": "UTF-8",
                "target": uid,
                "style": "display: none;"
            }
        ).append(
            [
                $(
                    "<input>",
                    {
                        "type": "hidden",
                        "name": "action",
                        "value": action
                    }
                ),
                $(
                    "<input>",
                    {
                        "type": "hidden",
                        "name": "field",
                        "value": field
                    }
                ),
                $(
                    "<input>",
                    {
                        "type": "hidden",
                        "name": "content",
                        "value": content
                    }
                ),
                $(
                    "<input>",
                    {
                        "type": "hidden",
                        "name": "hash",
                        "value": Visitor.token
                    }
                )
            ]
        );

        if (!!context) {
            form.append(
                $(
                    "<input>",
                    {
                        "type": "hidden",
                        "name": "context",
                        "value": context
                    }
                )
            );
        }

        form.insertAfter("#content");

        // Build and display the named iframe.
        $(
            "<div>",
            {
                "role": "dialog",
                "aria-label": '<?php esce(__("Modal window", "admin")); ?>'
            }
        ).addClass(
            "iframe_background"
        ).append(
            [
                $(
                    "<iframe>",
                    {
                        "name": uid,
                        "title": '<?php esce(__("Preview content", "admin")); ?>',
                        "sandbox": "allow-same-origin allow-popups allow-popups-to-escape-sandbox"
                    }
                ).addClass(
                    "iframe_foreground"
                ).loader().on(
                    "load",
                    function() {
                        if (
                            !!this.contentWindow.location
                            && this.contentWindow.location != "about:blank"
                        ) {
                            $(this).loader(true);
                        }
                    }
                ),
                $(
                    "<a>",
                    {
                        "href": "#",
                        "role": "button",
                        "accesskey": "x",
                        "aria-label": '<?php esce(__("Close", "admin")); ?>'
                    }
                ).addClass(
                    "iframe_close_gadget"
                ).click(
                    function(e) {
                        e.preventDefault();
                        $(this).parent().remove();
                    }
                ).append(
                    '<?php esce(icon_svg("close.svg")); ?>'
                )
            ]
        ).click(
            function(e) {
                if (e.target === e.currentTarget)
                    $(this).remove();
            }
        ).insertAfter("#content").children("a.iframe_close_gadget").focus();

        // Submit the form and destroy it immediately.
        $("#" + uid).submit().remove();
    }
}
var Settings = {
    init: function(
    ) {
        $("input#email_correspondence").click(
            function() {
                if ($(this).prop("checked") == false)
                    $("#email_activation").prop("checked", false);
            }
        );

        $("input#email_activation").click(
            function() {
                if ($(this).prop("checked") == true)
                    $("#email_correspondence").prop("checked", true);
            }
        );

        $("form#route_settings input[name='post_url']").on(
            "keyup",
            function(e) {
                $("form#route_settings code.syntax").each(
                    function() {
                        var syntax = $(this).html();

                        var regexp = new RegExp(
                            "(/?|^)" + escapeRegExp(syntax) + "(/?|$)", "g"
                        );

                        if ($(e.target).val().match(regexp))
                            $(this).addClass("tag_added");
                        else
                            $(this).removeClass("tag_added");
                    }
                );
            }
        ).trigger("keyup");
    }
}
<?php $trigger->call("admin_javascript"); ?>

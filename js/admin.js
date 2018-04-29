"use strict";
// global object for external things to access
var tsAdmin = {};

// use this style noconflict instead of normal "jQuery(function($) {" because
// this gets executed immediately, allowing us to use tsAdmin.setupMenu()
// before the page fully loads
(function($) {
    tsAdmin.setupMenu = function() {
        // ==================================================
        // nav menu

        // round the left & right sides of the main navemenu.  It'll either be
        // a link inside the first li, or the first li itself if it's nolink.
        $("div.navmenu > .navmenuleft > li:first-child > a").addClass("ui-corner-left");
        $("div.navmenu > .navmenuleft > li.nolink:first-child").addClass("ui-corner-left");
        $("div.navmenu > .navmenuright > li:last-child > a").addClass("ui-corner-right");
        $("div.navmenu > .navmenuright > li.nolink:last-child").addClass("ui-corner-right");
        // round the upper & lower corners of all submenus
        $("div.navmenu > ul ul").addClass("ui-corner-all");
        $("div.navmenu > ul ul > li:first-child > a").addClass("ui-corner-top");
        $("div.navmenu > ul ul > li:last-child > a").addClass("ui-corner-bottom");

        // Add an empty span to the navmenu <li>s that have <ul> submenus
        // The empty span gets gets styled by the CSS as a down arrow.
        $("div.navmenu > ul > li > ul").parent().children("a").prepend("<span class='ui-icon ui-icon-triangle-1-s'><\/span>");

        $("div.navmenu > ul li a").addClass("ui-state-default");
        // add a black arrow to submenu items with a nested menu
        $("div.navmenu > ul.navmenuright > li li > ul").parent().children("a").prepend("<span class='ui-icon ui-icon-triangle-1-w'><\/span> ");
        
        // toggle the submenu (if any) on hover.
        $("div.navmenu ul li").hover(function(e) {
            var li = $(this);
            li.children("a").toggleClass('ui-state-hover');
            clearTimeout(li.data("hidedelay"));
            // hide any other menus
            li.siblings("li").each(function() {
                if(li[0] != $(this)[0]) {
                    $(this).children("ul").hide();
                }
            });
            $(this).children("ul").show();
        }, function() {
            var li = $(this);
            li.children("a").toggleClass('ui-state-hover');
            li.data("hidedelay", setTimeout(function() {
                li.children("ul").hide();}, 500));
        });
        
        // set the top offset for submenus (appropriate CSS values vary
        // by browser by a few pixels, just do it in jquery).
        // Has to be done on show,  as height() is 0 while hidden.
        $("div.navmenu > ul > li").hover(
            function() {
                var liHeight = ($(this).outerHeight()) + "px";
                $(this).children("ul").css("top", liHeight);
            },
            function() { return; }
        );


        // set the horizntal offsets for nested submenus appropriately when hovered
        // has to be done on show, as whdith()is 0 while hidden
        $("div.navmenu > ul.navmenuleft li li ul").parent().hover(
            function(e) {
                $(this).children("ul").css("left", Math.floor($(this).width()) + "px");
            },
            function() { return; }
        );
        $("div.navmenu > ul.navmenuright li li ul").parent().hover(
            function(e) {
                $(this).children("ul").css("right", Math.floor($(this).width()) + "px");
            },
            function() { return; }
        );

        // close an open menu if users hit ESC.  It just feels right.
        $(document).on("keydown", function(e) {
            if(e.which === 27) {
                $("div.navmenu > ul > li > ul:visible").hide();
            }
        });
        // close an open menu if users click anywhere empty
        $(document).on("click", function() {
            $("div.navmenu > ul > li > ul:visible").hide();
        });

        // debug: clicking on thing with submenu makes hover stay
        // $("div.navmenu li ul").parent().click(function (e) {
        //    $(this).unbind("mouseenter mouseleave");
        //    e.preventDefault();
        // });

        // don't allow text selection on disabled menu items, it's dorky
        $("div.navmenu li.nolink").disableSelection().css("cursor", "default");
    };

    // ==================================================
    // other random stuff

    // takes a time duration in seconds, returns a string in the format "3h 24m"
    function hms(val) {
        var str="", numPrinted=0, precision=2;

        // days
        if(val > (60 * 60 * 24)) {
            var days = Math.flloor(val / (60 * 60 * 24));
            val = val - (days * 60 * 60 * 24);
            str += days + "d ";
            numPrinted++;
        }

        // hours
        if(val > 60 * 60) {
            var hours = Math.floor(val  / (60 * 60));
            val = val - (hours * 60 * 60);
            str += hours + "h ";
            numPrinted++;
        }

        // minutes
        if(numPrinted < precision && val > 60) {
            var minutes = Math.floor(val / 60);
            val = val - minutes * 60;
            str += minutes + "m ";
            numPrinted++;
        }

        // seconds
        if(numPrinted < precision) {
            str += val + "s ";
            numPrinted++;
        }

        return str.replace(/ +$/, "");
    }

    function parseQueryString(queryString) {
        var args = {};
        var values =  queryString.split("&");
        var i, pair;
        for(i=0; i<values.length; i++) {
            pair = values[i].split("=");
            args[decodeURIComponent(pair[0].replace(/\+/g, ' '))] = decodeURIComponent(pair[1].replace(/\+/g, ' '));
        }
        return args;
    }

    /* dynamically add a box to the page.
     * see also <box> in webinatoradmin.src for vortex-generated msgs */
    function box(type, msg) {
        var icon, typeClass;
        if(type === undefined) {
            type="error";
        }
        switch(type) {
        case "info":
            typeClass="boxInfo ui-state-highlight";
            icon="info";
            break;
        case "warning":
            typeClass="boxInfo ui-state-highlight";
            icon="info";
            break;
        case "error":
            typeClass="boxError ui-state-error";
            icon="alert";
            break;
        }
        var newBox = $('<div class="box ' + typeClass + ' ui-widget ui-corner-all clearAfter"><span class="ui-icon ui-icon-' + icon + '"></span><span>' + msg + '</span></div>');
        return newBox;
    }

    /* generates sequence of hues, each separate from nearby hues.
     * idea from http://ridiculousfish.com/blog/posts/colors.html */
    function getHue(idx) {
        /* Here we use 31 bit numbers because JavaScript doesn't have a 32 bit unsigned type, and so the conversion to float would produce a negative value. */
        var bitcount = 31;
        
        /* Reverse the bits of idx into ridx */
        var ridx = 0, i = 0;
        for (i=0; i < bitcount; i++) {
            ridx = (ridx << 1) | (idx & 1);
            idx >>>= 1;
        }
        
        /* Divide by 2**bitcount */
        var hue = ridx / Math.pow(2, bitcount);
        
        /* Start at .6 (216 degrees) */
        return (hue + 0.6) % 1;
    }
    var nextHueIndex=0;
    function getNextHue() {
        var idx=nextHueIndex++;
        return getHue(idx);
    }
    function getColor(idx, brightness) {
        return "hsl(" + Math.round(getHue(idx)*360) + ", 100%, " + brightness + ")";
    }
    function getNextColor() {
        return "hsl(" + Math.round(getNextHue()*360) + ", 100%, 65%)";
    }

    tsAdmin.undoableOptions = {
        inlineStyling: false,
        getPostData: function() {
            return {
                subject: "",
                predicate: "Update to save deletion"
            };
        },
        // get the thing to be hidden, starting from the .deleteHandle
        getTarget: function(clickSource) {
            var parent = clickSource.parent();
            if(parent[0].nodeName.toLowerCase() === "td") {
                parent = parent.parent();
            }
            return parent;
        },
        createUndoable: function(target, data, message) {
            var content = '<span class="undo"><a href="#' + data.id + '"><img class="undoHandle jsonly" src="' + COMMON_DIR + '/icons/16x16/general/undo.png"/>Undo</a></span>' + '<span class="status">' + message + '</span>';
            if (target[0].tagName === 'TR') {
                var colSpan = 0;
                target.children('td').each(function() {
                    if($(this).attr("colspan")) {
                        colSpan += parseInt($(this).attr("colspan"), 10);
                    } else {
                        colSpan += 1;
                    }
                });
                target.after('<tr><td class="undoable" colspan="' + colSpan + '">' + content + '</td></tr>');
            }
            else {
                var tagName = target[0].tagName;
                var classes = target.attr('class') || "";
                target.after('<' + tagName + ' class="undoable ' + classes + '">' + content + '</' + tagName + '>');
            }
            return target.next();
        },
        hidingStatus: function(undoRow, target) {
            // move the target row back to sibling of undoRow (which will disappear)
            undoRow.after(target);
            // real row is showing, re-enable things so they'll submit
            target.find("input, select, textarea").prop("disabled", false);
            target.find(".deleteInverted").prop("disabled", true);
        },
        showingStatus: function(undoRow) {
            var target = undoRow.prev();
            undoRow.height(target.innerHeight() - 2); // 2 for undoRow's border
            if (target[0].tagName === 'TR') {
                // force the undo row to be just as wide as the original so the table
                // doesn't snap down to nothingness when all rows are deleted
                undoRow.find("td")[0].style.width = (target.width()-2) + "px";
            }
            // undo is showing, so disable stuff in the real, hiding row so that
            // it won't be sent on submission (fulfilling the "delete").
            target.find("input, select, textarea").prop("disabled", true);
            target.find(".deleteInverted").prop("disabled", false);
            // make target a child of the undoRow so it follows through sorting
            undoRow.append(target);
        }
    };

    // updates our sortable functionality when rows are dynamically added/removed.
    function updateSortable(container) {
        // only count displayable tags in children, to avoid <noscript> & such
        var numChildren = $(container).children("div, tr").length;
        // if it's a <table> container, only show <thead> if there's at least 1 row
        if(container.nodeName.toLowerCase() === "tbody") {
            var thead = container.parentNode.children[0];
            if(thead.nodeName.toLowerCase() === "thead") {
                // ie7 can't take the technically acurate "table-row",
                // setting to empty resets it to its default value 
                $(thead.children[0]).css("display",
                                         ((numChildren !== 0) ? "" : "none"));
            }
        }
        // only show sorting handles if there's more than 1 item
        $(container).find(".sortHandle").css("display", 
                                             ((numChildren > 1) ? "inline" : "none"));
    }

    // config forms use this to store the templates that 'Add Additional XXX' use
    tsAdmin.rowTemplate = [];

    function getTemplate(paramname) {
        if("" === paramname) {
            alert("!!! no data-paramname attribute found");
            return;
        }
        var template = tsAdmin.rowTemplate[paramname];
        if(template === undefined) {
            // should never happen
            alert("Template '"+paramname+"' not found");
            return;
        }
        // replace any !TS_SUFFIX token with a unique token.  These uniquely named
        // form elements will be combined on the server so it doesn't really
        // matter what the suffix is, just needs to be unique so radio buttons
        // stay grouped together properly.
        addRow.counter++;
        template = template.replace(/!TS_SUFFIX/g, addRow.counter.toString() + "js" );
        return template;
    }

    function addRow(event) {
        /*jshint validthis: true */
        event.preventDefault();
        var paramname = $(this).data("paramname");
        var template = getTemplate(paramname);
        
        var container = document.getElementById(paramname + "_container");
        if(container === undefined) {
            // should never happen
            alert("Container '"+paramname+"_container' not found");
            return;
        }
        // ensure this doesn't put us above max rows (if defined)
        var maxRows = $(container).data("maxrows");
        if(maxRows !== undefined && container.children.length >= maxRows) {
            alert("Only " + maxRows + " rows allowed.");
            return;
        }

        var newRow = $(template);
        initRow(newRow);
        $(container).append(newRow);
        newRow.find(".deleteHandle").click(function() {
            newRow.remove();
            updateSortable(container);
        });
        newRow.find(".collapseEditHandle").remove();

        // force IE7/IE8 to reflow
        // http://stackoverflow.com/questions/1702399/how-can-i-force-reflow-after-dhtml-change-in-ie7
        try {
            container.parentNode.style.cssText += "";
            container.parentNode.style.zoom = 1;
            container.style.cssText += "";
            container.style.zoom = 1;
        }catch(ignore){}
        updateSortable(container);
    }
    addRow.counter=0;

    function getValueMap(container) {
        var map = {};
        container.find("input,select").each(function() {
            map[$(this).attr("name")] = $(this).val();
        });
        return map;
    }
    function getHiddenPlaceholderMap(originalRow) {
        var hiddenPlaceholder = originalRow.find(".hiddenPlaceholder");
        var map = getValueMap(hiddenPlaceholder);
        // hiddenPlaceholder will no longer be used, now the editRow will
        // always be submitting the values
        hiddenPlaceholder.remove();
        return map;
    }

    function editRow() {
        /*jshint validthis: true */
        // find our proper parent (tr or div)
        var originalRow = $(this).parent();
        if(originalRow[0].nodeName.toLowerCase() === "td") {
            originalRow = originalRow.parent();
        }
        // if there's already an editRow, use it.  otherwise create one
        var editingRow = originalRow.children(".editRow");
        if(editingRow.length === 0) {
            var originalRowHeight = originalRow.innerHeight();
            
            var paramname = originalRow.data("paramname");
            var template = getTemplate(paramname);
            editingRow = $(template);
            editingRow.addClass("editRow");
            // give editRow a min-height of the existing originalRow.  This allows the slide
            // animations to work properly, by starting/stopping at the existing originalRow
            // instead of 0 and then "snapping" to the existing originalRow.
            editingRow.css("min-height", originalRowHeight);
            
            // shove our data into the template.  Could come from an editvalues
            // attribute, or from a .hiddenPlaceholder block of hidden inputs
            var keys, valueMap;
            var editvalues = originalRow.data("editvalues");
            if(editvalues) {
                valueMap = parseQueryString(editvalues);
            } else {
                valueMap = getHiddenPlaceholderMap(originalRow);
            }
            keys = Object.keys(valueMap);
            var i, widget, tag;
            for(i=0; i<keys.length; i++) {
                widget = editingRow.find("*[name='" + keys[i] + "']");
                if(widget.length !== 0) {
                    tag = $(widget)[0].tagName;
                    if(tag === "INPUT" || tag === "TEXTAREA") {
                        $(widget).val(valueMap[keys[i]]);
                    }
                }
            }

            editingRow.find(".deleteHandle").undoable(tsAdmin.undoableOptions);
            // make the 'edit' handle in our editRow apply the edit
            editingRow.find(".collapseEditHandle").click(function() {
                // change the editRow icon to "edited"
                originalRow.find(".editHandle").each(function() {
                    $(this).attr("src", $(this).attr("src").replace("pencil.png", "pencil_asterisk.png"));
                });
                editingRow.slideUp({
                    complete: function() { applyEdit(originalRow, editingRow); },
                    duration: "fast"
                });
            });
        }
        // remove originalRow, add editingRow, and make it slide down
        editingRow.insertAfter(originalRow);
        editingRow.append(originalRow);
        originalRow.hide();
        editingRow.hide();
        editingRow.slideDown("fast");
        originalRow.find("input, select, textarea").prop("disabled", true);
    }


    function applyEdit(originalRow, editingRow) {
        // hide the editRow and shove it inside originalRow for later use
        editingRow.hide(); 
        originalRow.insertAfter(editingRow);
        originalRow.append(editingRow);
        originalRow.show();
        originalRow.find("input, select, textarea").prop("disabled", false);

        originalRow.find(".deleteInverted").prop("disabled", true);
        // try taking current values from editingRow and update corresponding fields
        // in originalRow
        var valueMap = getValueMap(editingRow);
        var keys = Object.keys(valueMap);
        var i;
        for(i=0; i<keys.length; i++) {
            originalRow.find("*[data-sourceField='" + keys[i] + "']").html(valueMap[keys[i]]);
        }
    }

    function clearDummyFields() {
        $("#configSettings.walkForm input[name^='dummy_']").val("");
    }

    /* Adds "Update and XXX" to the "GO" or "STOP" button on the settings
     * page.  Only gets called once. */
    function updateGoButtonText() {
        $("form#configSettings.walkForm input#goButton").val(function(i, val) {
            return "Update and " + val;
        });
    }

    /* Updates whether the "GO" button for walks is disabled.  This can be
     * permanently (from the page's scope) disabled due to permissions or
     * sysDenyWalk (which is stored in a form data attribute), or because
     * the current form has denyWalk. */
    function updateGoButtonDisabled() {
        $("form#configSettings.walkForm").each(function() {
            var disabledReason;
            // disable if the form has a forced reason (perm, sysDisableWalks, etc)
            var forceDisabled = $(this).data('walkdisabledreason');
            if(forceDisabled !== undefined) {
                disabledReason=forceDisabled;
            } else {
                // disable if this form's SSc_disablewalks is set
                if($(this).find("input[name='SSc_disablewalks_g0']:checked").val() === "Y") {
                    disabledReason="'Disable Walks' set";
                }
            }
            if(disabledReason !== undefined) {
                $(this).find("input#goButton")
                    .prop("disabled", true)
                    .prop("title", disabledReason);
            } else {
                $(this).find("input#goButton")
                    .prop("disabled", false)
                    .prop("title", "");
            }
        });
    }

    function updateTheme() {
        /*jshint validthis: true */
        var current = $("#jqueryuiCss").attr("href");
        // replace jqueryui's directory before the CSS file with the new theme
        // From: /webinator/common/css/jquery-ui-themes-1.10.2/le-frog/jquery-ui.min.css
        //   To: /webinator/common/css/jquery-ui-themes-1.10.2/ui-darkness/jquery-ui.min.css
        current = current.replace(/[^\/]+(\/[^\/]+.css)$/, $(this).val() + "$1");
        $("#jqueryuiCss").attr("href", current);
        if($("#adminThemeNote").length === 0) {
            $(this).after("<span id='adminThemeNote' class='ui-widget ui-corner-all ui-state-highlight'><span class='ui-icon ui-icon-info'/>Update to save changes</span>");
        }
    }

    tsAdmin.initCheckAll = function() {
        $(".checkAll:checkbox").each(function() {
            var checkAll = $(this);
            var nameSelector;
            var nameGlob;
            var form=checkAll.closest("form");
            var name=checkAll.data("group");
            if(name) {
                nameSelector = "name='" + name + "'";
            } else {
                // We often use foo_g0, foo_g1, foo_g2 for groups of
                // checkboxes.  providing data-groupGlob means we should look
                // for checkboxes where the name starts with FOO_g*
                nameGlob = checkAll.data("groupglob");
                if(nameGlob) {
                    nameSelector="name^='" + nameGlob + "'";
                } else {
                    // group and groupGlob failed?  Set the selector to something
                    // that'll never match so this will silently go away.
                    nameSelector="name='CHECKALL_ERROR'";
                }
            }
            
            // declare updateCheckAll inside this .each for closure, we use it
            // immediately to set this .checkAll properly on start, and we also
            // call it as a singles callback.
            var updateCheckAll = function() {
                // update any .checkAll boxes based on whether the total combination
                // of these checkboxes is all checked, all unchecked, 
                // or a combination ("indeterminate").
                var numChecked =   form.find("input:checkbox:enabled:checked[" +nameSelector + "]").length;
                var numUnchecked = form.find("input:checkbox:enabled:not(:checked)[" + nameSelector + "]").length;
                if(numChecked !== 0 && numUnchecked !== 0) {
                    checkAll.prop("indeterminate", true);
                } else {
                    checkAll.prop("indeterminate", false);
                    checkAll.prop("checked", numChecked !== 0);
                }
                // Also enable/disable any .checkAllEnabled based on whether there
                // are any of this group of checkboxes checked
                form.find(".checkAllEnabled").prop("disabled", numChecked===0);
            };

            // when this .checkAll is clicked, set all the .checkAllSingle boxes
            // to whatever this .checkAll was just set to
            checkAll.on("click", function() {
                var checked = $(this).prop("checked");
                form.find("input:checkbox:enabled[" + nameSelector + "]")
                    .prop("checked", checked);
                // Also enable/disable any .checkAllEnabled based on whether there are
                // any of this group checked
                form.find(".checkAllEnabled").prop("disabled", !checked);
            });

            // hide this .checkAll if there are no checkboxes for it.
            // Might have zero when we print this in an upgradeData table header,
            // and then none of the profiles need upgrading.
            var singles = form.find("input:checkbox[" + nameSelector + "]");
            if(singles.length < 1) {
                $(this).css("display", "none");
                // Also hide any label(s) for our checkAll checkbox:
                var id = $(this).attr("id");
                $("label[for='" + id + "']").css("display", "none");
            } else {
                singles.on("click", updateCheckAll);
                updateCheckAll();
                checkAll.attr("title", "Check/Uncheck all");
                // disable .checkAllEnabled items at start if no checkboxes are checked
                $(".checkAllEnabled").each(function() {
                    $(this).prop("disabled",
                                 $(this).closest("form").find("input:enabled:checked").length === 0);
                });
            }
        });
    };

    function psValidateFields() {
        // use our jQuery xml helpers until admin interface supports JSON
        var doc = $.createNew('XMLDocument');
        var root = doc.appendNew('psfields');
        $("#parametricFields tbody tr").each(function() {
            var field = root.appendNew("field");
            field.appendNew('name').text($(this).find("*[name='SSc_psnameInput']").val());
            field.appendNew('type').text($(this).find("*[name='SSc_pstypeInput']").val());
        });
        $.ajax({
            data: doc.html(),
            dataType: "xml",
            error: function(jqXHR, textStatus, errorThrown) {
                alert("error: " + textStatus + " (" + errorThrown + ")");
            },
            processData: false,
            success: function(data, textStatus, jqXHR) {
                var status = $(data).find("status");
                $("#parametricFields .psFieldStatus").each(function(i) {
                    var statusText = $(status[i]).text();
                    if(statusText !== "") {
                        $(this).html("<span class='ui-icon ui-icon-"+ 
                                     ( (statusText === "ok") ? "circle-check" : "alert" ) +
                                     "'></span>" + statusText);
                    } else {
                        $(this).html("");
                    }
                });
            },
            type: "POST",
            url: "ajaxPSValidateFields.xml"
        });
    }

    tsAdmin.updateDocumentUsageOverview = function(node, force) {
        if(node === undefined) {
            node = $(".documentUsageOverview");
        }
        node=$(node);
        var canvas2DSupported = !!window.CanvasRenderingContext2D;
        var pieOptions  = {
            legendTemplate: "<tbody class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<segments.length; i++){%><tr><td class=\"pieLegendBlock\" style=\"background-color:<%=segments[i].fillColor%>\"></td><td class=\"pieLegendNumber\"><%if(segments[i].label){%><%=segments[i].value.toLocaleString()%></td><td> - <a href=\"report.html?r=SSc_longreport&profile=<%=segments[i].label%>\"><%=segments[i].label%></a><%}%></td></tr><%}%></tbody>",
            segmentShowStroke: false
        };

        // force things into a "loading" state, we may be Forcing Refresh
        node.find(".usageLabel").html("&nbsp;");
        var bar = node.find(".txProgressBar");
        bar.progressbar("option", "value", false);
        node.find(".progress-label").html("Loading...");
        node.find(".box").remove();
        var pieCanvas = $("#pieCanvas")[0];
        if(canvas2DSupported && pieCanvas) {
            pieCanvas.getContext("2d").clearRect(0, 0, pieCanvas.width, pieCanvas.height);
            $("#pieLegend").empty();
        }

        $.ajax({
            url: "getDocumentUsageOverviewJson.json?force=" + force,
            dataType: "json",
            error: function(jqXHR, textStatus, errorThrown ) {
                node.after(box("error", "error loading content - " + textStatus + " (" + errorThrown + ")"));
                bar.remove();
            },
            success: function(data) {
                if(data.error !== undefined) {
                    node.after(box("error", data.error));
                    $(".txProgressBar").remove();
                    return;
                }
                var percent, label;
                if(data.warnMsg) {
                    label = "Accessible Profiles' Document Usage: ";
                    // only add the note on the "full" page, which has the pieChart
                    if(canvas2DSupported && pieCanvas) {
                        node.after(box("info", data.warnMsg));
                    }
                } else {
                    label = "Total Document Usage: ";
                }
                if(data.countLimit!==0) {
                    percent = 100 * data.countTotal / data.countLimit;
                    label += Math.round(percent) + "%  (" + data.countTotal.toLocaleString() + " / " + data.countLimit.toLocaleString() + ")";
                } else {
                    // no limit, just display the number
                    percent=0;
                    label += data.countTotal.toLocaleString();
                }
                if(data.errMsg) {
                    node.after(box("error", data.errMsg));
                }
                bar.progressbar("option", "value", percent);
                node.find(".progress-label").html(label);
                node.find(".usageLabel").html("<div class='forceRefresh'>Updated " + data.timediff + " ago <a href='javascript:tsAdmin.updateDocumentUsageOverview(undefined, true)'>[Recalculate]</a></div>");

                if(pieCanvas) {
                    var i;
                    if(canvas2DSupported) {
                        // add some colors to data
                        for(i=0;i < data.profiles.length;i++) {
                            data.profiles[i].color = getColor(i, "65%");
                            data.profiles[i].highlight = getColor(i, "85%");
                        }
                        var ctx = pieCanvas.getContext("2d");
                        var myPie = new Chart(ctx).Doughnut(data.profiles,pieOptions);
                        var legend = node.find("#pieLegend");
                        legend.html(myPie.generateLegend());
                        var helpers = Chart.helpers;
                        helpers.each(legend[0].firstChild.childNodes, function(legendNode, index) {
                            helpers.addEvent(legendNode, 'mouseover', function() {
                                var segment = myPie.segments[index];
                                segment.save();
                                segment.fillColor = segment.highlightColor;
                                myPie.showTooltip([segment]);
                                segment.restore();
                            });
                        });
                        helpers.addEvent(legend[0].firstChild, 'mouseleave', function() {
                            myPie.activeElements=[];
                            myPie.draw();
                        });
                        helpers.addEvent(pieCanvas, "mousemove", function(evt) {
                            var segments = myPie.getSegmentsAtEvent(evt);
                            if(segments.length !== 0) {
                                pieCanvas.style.cursor = "pointer";
                            } else {
                                pieCanvas.style.cursor = "default";
                            }
                        });
                        $("#pieCanvas").on("click", function(evt) {
                            var segments = myPie.getSegmentsAtEvent(evt);
                            if(segments.length!==0) {
                                document.location = "report.html?r=SSc_longreport&profile=" + segments[0].label;
                            }
                        });
                    } else {
                        // we wanted the pie, but it's unsupported.  Just print
                        // the raw data since we won't get the legend
                        var content="";
                        for(i=0;i < data.profiles.length;i++) {
                            content += data.profiles[i].value + " - " + data.profiles[i].label + "<br/>";
                        }
                        node.find("#pieLegend").html(content);
                    }
                }
            }
        });
    };

    // date formatter used by things like dashboard
    tsAdmin.formatDate = function(d) {
        var month = (d.getMonth+1 < 10 ? '0' : '') + (d.getMonth() + 1),
        day = (d.getDate() < 10 ? '0' : '') + d.getDate(),
        hour = (d.getHours() < 10 ? '0' : '') + d.getHours(),
        minute = (d.getMinutes() < 10 ? '0' : '') + d.getMinutes();
        return month + '/' + day + ' ' + hour + ':' + minute;
    };

    /* So.  If the date on the server is more than 1 year behind the
     * client browser's date, the login is never gonna work.  Our cookies
     * come with a expiration of 1 year.  So our response will include
     * Set-Cookie, and the browser will properly NOT include the cookie
     * in the next request, and they'll need to login again. 
     * 
     * Use a bit of javascript in browser to compare pageTimestamp to now,
     * and warn if it's greater than 1 year.
     * This is really only likely to happen when restoring a really old
     * snapshot of a virtual appliance.
     */
    tsAdmin.checkServerDateSanity = function() {
        var serverDateStr = $(".pageTimestamp").text().trim();
        if(!serverDateStr) {
            return;
        }
        // date is "YYYY-MM-DD HH:MM:SS ZZZ", change to "YYYY-MMMM-DD" for js
        serverDateStr = serverDateStr.substr(0, serverDateStr.indexOf(" "));
        if(!serverDateStr) {
            return;
        }
        var serverDate = new Date(serverDateStr);
        var browserDate = new Date(Date.now());
        if(!serverDate || !browserDate) {
            return;
        }
        var timeDiff = browserDate.getTime() - serverDate.getTime();
        var diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24));
        if(diffDays > 364) {
            alert("WARNING: server date (" + serverDate + ") is more than 1 year behind browser date (" + browserDate + "), login will not work.  Please fix server date.");
        }
    };
    // Simple helper function creates a new element from a name
    $.createNew = function(name) {
        return $('<'+name+' />');
    };

    // JQ plugin appends a new element created from 'name' to each matched element.
    $.fn.appendNew = function(name) {
        var node = $.createNew(name);
        $(this).append(node);
        return node;
    };

    function initRow(row) {
        // number spinners
        row.find("input.spinner").spinner();
    }

    tsAdmin.init = function() {
        $("#tabs").tabs();
        $(".uiButton").each(function() { $(this).button({icons: {primary: $(this).data("icon")}}); });
        $(".addHandle").on("click", addRow);
        $(".editHandle").on("click", editRow);
        tsAdmin.initCheckAll();
        $(".sortable")
            .sortable({
                cursor: 'n-resize',
                handle: '.sortHandle',
                // revert: 200,
                start: function(e, ui) {
                    ui.placeholder.height(ui.helper.outerHeight());
                },
                update: function() {
                    $(this).closest("form").trigger("change");
                }
            })
            .each(function() { updateSortable($(this)[0]); });
        $(".deleteHandle").undoable(tsAdmin.undoableOptions);

        $("#configSettings")
            .on("submit", clearDummyFields)
            .on("change.updateButton", function() {
                $(this).off("change.updateButton");
                updateGoButtonText();
            })
            .on("change", "input[name='SSc_disablewalks_g0']", updateGoButtonDisabled);

        // fire it on page loads so it sets disabled based on current config
        updateGoButtonDisabled();

        // Update the page's current theme when the admin theme setting changes
        $("#configSettings select[name='SSc_adminTheme']").on("change", updateTheme);
        $("#parametricFields")
            .on("keyup change", function() {
                clearTimeout($(this).data('timer'));
                $(this).data('timer', setTimeout(psValidateFields, 250));
            });
        $(".hideWhileLoading").removeClass("hideWhileLoading");

        // number spinners
        $("input.spinner").spinner();

        // help popups
        $(".helpText").dialog({
            autoOpen: false,
            minWidth: 450
        });
        $(".helpHandle").on("click", function() {
            var targetSelector = "#" + $(this).data("target");
            var text = $(targetSelector);
            text.dialog("open");
        });

        $(".txProgressBar").each(function() {
            $(this).progressbar({
                value: $(this).data("value")
            });
        });

        $(".documentUsageOverview").each(function() { tsAdmin.updateDocumentUsageOverview(this, false);});
    };
})(jQuery);

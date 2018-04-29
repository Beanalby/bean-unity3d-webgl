"use strict";
/*jslint node: true */
/*jslint browser: true*/
/*global jQuery */

var beanAdmin = {};

(function($) {
    beanAdmin.initCheckAll = function() {
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

    beanAdmin.init = function() {
        beanAdmin.initCheckAll();
    };

    $(document).ready(function() {
        beanAdmin.init();
    });
})(jQuery);

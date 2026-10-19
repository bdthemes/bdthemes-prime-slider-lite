(function (window, document, $) {
    "use strict";
    var BdThemesLiveCopy = {
        //Initializing properties and methods
        init: function (e) {
            BdThemesLiveCopy.loadContextMenuGroupsHooks();
        },
        loadContextMenuGroupsHooks: function () {
            elementor.hooks.addFilter(
                "elements/container/contextMenuGroups",
                function (groups, element) {
                    return BdThemesLiveCopy.prepareMenuItem(groups, element);
                }
            );

            elementor.hooks.addFilter(
                "elements/section/contextMenuGroups",
                function (groups, element) {
                    return BdThemesLiveCopy.prepareMenuItem(groups, element);
                }
            );

            elementor.hooks.addFilter(
                "elements/widget/contextMenuGroups",
                function (groups, element) {
                    return BdThemesLiveCopy.prepareMenuItem(groups, element);
                }
            );

            elementor.hooks.addFilter(
                "elements/column/contextMenuGroups",
                function (groups, element) {
                    return BdThemesLiveCopy.prepareMenuItem(groups, element);
                }
            );
        },
        prepareMenuItem: function (groups, element) {
            var index = _.findIndex(groups, function (element) {
                return "clipboard" === element.name;
            });

            // Both actions run Elementor's own commands. Its Copy writes the
            // element to the system clipboard as well as to local storage, and
            // "Paste from other site" reads it back, so this works across
            // sites. The items used to show a "deprecated" alert instead.
            var getContainers = function () {
                var container = element.getContainer();
                return elementor.selection.getElements(container);
            };

            groups.splice(index + 1, 0, {
                name: "bdt-ep-live-copy-paste",
                actions: [
                    {
                        name: "ep-live-copy",
                        title: "Live Copy",
                        icon: "eicon-copy",
                        isEnabled: function () {
                            return elementor.selection.isSameType() && !element.getContainer().isLocked();
                        },
                        callback: function () {
                            return $e.run("document/elements/copy", {
                                containers: getContainers(),
                            });
                        },
                    },
                    {
                        name: "ep-live-paste",
                        title: "Live Paste",
                        icon: "eicon-import-export",
                        callback: function () {
                            return $e.run("document/elements/paste-area", {
                                container: element.getContainer(),
                            });
                        },
                    },
                ],
            });

            return groups;
        },
    };
    BdThemesLiveCopy.init();
})(window, document, jQuery);

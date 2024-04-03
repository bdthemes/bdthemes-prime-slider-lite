(function ($) {
	"use strict";

	var PrimeSliderEditor = {
		init: function () {
			elementor.channels.editor.on(
				"section:activated",
				PrimeSliderEditor.onAnimatedBoxSectionActivated,
			);

			window.elementor.on("preview:loaded", function () {
				elementor.$preview[0].contentWindow.PrimeSliderEditor =
					PrimeSliderEditor;
				PrimeSliderEditor.onPreviewLoaded();
			});
			// eslint-disable-next-line prettier/prettier
			elementor.channels.editor.on(
				"primeSliderProBuilderSetting:applySinglePagePostOnPreview",
				PrimeSliderEditor.ApplyPreviewPostId,
			);
			// eslint-disable-next-line prettier/prettier
			elementor.channels.editor.on('saved', PrimeSliderEditor.savedBuilder);
		},
		savedBuilder: function () {
			if (PrimeSliderEditor.shouldReload) {
				PrimeSliderEditor.shouldReload = false;
				window.location.href = window.location.href;
			}
		},
		ApplyPreviewPostId: function () {
			PrimeSliderEditor.shouldReload = true;
			// eslint-disable-next-line prettier/prettier
			$('#elementor-panel-saver-button-publish').trigger('click');
			// eslint-disable-next-line prettier/prettier
		},
		onPreviewLoaded: function () {
			var elementorFrontend = $("#elementor-preview-iframe")[0].contentWindow
				.elementorFrontend;

			elementorFrontend.hooks.addAction(
				"frontend/element_ready/widget",
				function ($scope) {
					$scope
						.find(".ps-elementor-template-edit-link")
						.on("click", function (event) {
							window.open($(this).attr("href"));
						});
				},
			);
		},
	};

	$(window).on("elementor:init", PrimeSliderEditor.init);

	window.PrimeSliderEditor = PrimeSliderEditor;

	elementor.hooks.addFilter("panel/elements/regionViews", function (panel) {
		jQuery(document).ready(function () {
			jQuery("body").append(
				`<style>.bdt-pro-unlock-icon:after{right: auto !important; left: 5px !important;}</style>`,
			);
		});

		if (
			PrimeSliderConfigEditor.pro_license_activated ||
			PrimeSliderConfigEditor.promotional_widgets <= 0
		)
			return panel;

		var promotionalWidgetHandler,
			promotionalWidgets = PrimeSliderConfigEditor.promotional_widgets,
			elementsCollection = panel.elements.options.collection,
			categories = panel.categories.options.collection,
			categoriesView = panel.categories.view,
			elementsView = panel.elements.view,
			freeCategoryIndex,
			proWidgets = [];

		_.each(promotionalWidgets, function (widget, index) {
			elementsCollection.add({
				name: widget.name,
				title: widget.title,
				icon: widget.icon,
				categories: widget.categories,
				editable: false,
			});
		});

		elementsCollection.each(function (widget) {
			"ultimate-post-kit-pro" === widget.get("categories")[0] &&
				proWidgets.push(widget);
		});

		freeCategoryIndex = categories.findIndex({
			name: "ultimate-post-kit",
		});

		freeCategoryIndex &&
			categories.add(
				{
					name: "ultimate-post-kit-pro",
					title: "Ultimate Post Kit Pro",
					defaultActive: !1,
					items: proWidgets,
				},
				{
					at: freeCategoryIndex + 1,
				},
			);

		promotionalWidgetHandler = {
			getWedgetOption: function (name) {
				return promotionalWidgets.find(function (item) {
					return item.name == name;
				});
			},

			className: function () {
				var className = "elementor-element-wrapper";

				if (!this.isEditable()) {
					className += " elementor-element--promotion";
				}
				return className;
			},

			onMouseDown: function () {
				void this.constructor.__super__.onMouseDown.call(this);
				var promotion = this.getWedgetOption(this.model.get("name"));

				elementor.promotion.showDialog({
					title: sprintf(
						wp.i18n.__("%s", "elementor"),
						this.model.get("title"),
					),
					content: sprintf(
						wp.i18n.__(
							"Use %s widget and dozens more pro features to extend your toolbox and build sites faster and better.",
							"elementor",
						),
						this.model.get("title"),
					),
					targetElement: this.el,
					position: {
						blockStart: "-7",
					},
					actionButton: {
						url: promotion.action_button.url,
						text: promotion.action_button.text,
						classes: promotion.action_button.classes || [
							"elementor-button",
							"elementor-button-success",
						],
					},
				});
			},
		};

		panel.elements.view = elementsView.extend({
			childView: elementsView.prototype.childView.extend(
				promotionalWidgetHandler,
			),
		});

		panel.categories.view = categoriesView.extend({
			childView: categoriesView.prototype.childView.extend({
				childView:
					categoriesView.prototype.childView.prototype.childView.extend(
						promotionalWidgetHandler,
					),
			}),
		});

		return panel;
	});
})(jQuery);

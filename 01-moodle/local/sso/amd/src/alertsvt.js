define([
  "jquery",
  "core/modal_factory",
  "core/modal_events",
  "core/templates",
  "core/str",
], function ($, ModalFactory, ModalEvents, Templates, str) {
  return {
    init: function (params) {
      
        ModalFactory.create({
            // type: ModalFactory.types.SAVE_CANCEL,
            // title: "",
            body: Templates.render("local_sso/modalsvt", {
              // alerts: params.checks,
              // allowexclude: params.allowexclude,
            }),
          }).done(function (modal) {
            modal.show();
          });
    },
  };
});

{block name="backend/order/controller/list"}
{$smarty.block.parent}
Ext.define('Shopware.apps.MonduExtendOrder.view.list.List', {
  override: 'Shopware.apps.Order.controller.List',
  onSaveOrder: function (editor, event, store) {
    var record = store.getAt(event.rowIdx);
    if (record == null) {
      return;
    }

    var originalOrderSaveMethod = record.save;
    record.save = function (options) {
      var originalCallback = options.callback;
      options.callback = function (data, operation) {
        originalCallback.call(this, data, operation);
        store.load();
      };

      originalOrderSaveMethod.call(this, options);
    };

    this.callParent(arguments);

    record.save = originalOrderSaveMethod;
  }
});
{/block}
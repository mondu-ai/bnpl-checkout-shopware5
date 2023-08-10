window.addEventListener('DOMContentLoaded', () => {
    var onCancelOrder = function (event) {
        var target = this;
        event.preventDefault();

        postMessageApi.createConfirmMessage(
            'Cancel Order',
            'Are you sure you want to cancel this order?',
            function (data) {
                if ('yes' === data) {
                    callCancelOrderEndpoint.call(target)
                }
            }
        );
    };

    var onRefundOrder = function (event) {
        var target = this;
        event.preventDefault();

        postMessageApi.createPromptMessage(
            'Credit note',
            'Please enter credit amount',
            function (data) {
                if (data.btn === 'ok') {
                    callRefundOrderEndpoint.call(target, data.text);
                }
            }
        );
    };

    var onCancelInvoice = function (event) {
        var target = this;
        event.preventDefault();

        postMessageApi.createConfirmMessage(
            'Cancel Invoice',
            'Are you sure you want to cancel this invoice?',
            function (data) {
                if ('yes' === data) {
                    callCancelInvoiceEndpoint.call(target);
                }
            }
        );
    };

    var callCancelOrderEndpoint = function () {
        var formData = new FormData();
        formData.append('order_id', this.dataset.order_id);
        formData.append('__csrf_token', window.parent.Ext.CSRFService.getToken())
        fetch(this.dataset.action, {
            method: 'POST',
            body: formData
        })
          .then(response => response.json())
          .then((response) => {
            if (response.success) {
                postMessageApi.createAlertMessage('Success', 'Order canceled successfully');
                var stateContainer = document.getElementById('mondu-order-state');
                if(stateContainer) {
                    stateContainer.innerText = 'canceled';
                }
                this.setAttribute('disabled', '');
            } else {
                postMessageApi.createAlertMessage('Error', response.message);
            }
        });
    };

    var callRefundOrderEndpoint = function (amount) {
        var formData = new FormData();
        formData.append('order_id', this.dataset.order_id);
        formData.append('invoice_id', this.dataset.invoice_id);
        formData.append('amount', amount);
        formData.append('__csrf_token', window.parent.Ext.CSRFService.getToken());

        fetch(this.dataset.action, {
            method: 'POST',
            body: formData
        })
          .then(response => response.json())
          .then((response) => {
              if (response.success === true) {
                  postMessageApi.createAlertMessage(
                    'Success',
                    'Credit note created'
                  );
                  window.location.reload();
              } else {
                  postMessageApi.createAlertMessage(response.title ? response.title : 'Error', response.message);
              }
          });
    };

    var callCancelInvoiceEndpoint = function () {
        var formData = new FormData();
        formData.append('order_id', this.dataset.order_id);
        formData.append('invoice_id', this.dataset.invoice_id);
        formData.append('__csrf_token', window.parent.Ext.CSRFService.getToken());
        fetch(this.dataset.action, {
            method: 'POST',
            body: formData
        })
          .then(response => response.json())
          .then(response => {
              if (response.success) {
                  postMessageApi.createAlertMessage('Success', 'Invoice canceled successfully');
                  this.setAttribute('disabled', '');
                  location.reload();
              }
              else {
                  postMessageApi.createAlertMessage('Error', response.message);
              }
          });
    };

    (function () {
        var elementActionmapping = {
            '.cancel-order': onCancelOrder,
            '.refund-order': onRefundOrder,
            '.cancel-invoice': onCancelInvoice
        };

        Object.keys(elementActionmapping).forEach(key => {
            document.querySelectorAll(key).forEach(element => {
                element.addEventListener('click', elementActionmapping[key]);
            });
        });
    })();
});
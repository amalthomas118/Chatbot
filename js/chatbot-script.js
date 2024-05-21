jQuery(document).ready(function($) {
    $('#chatbot-button').click(function() {
        $('#chatbot-box').toggle();
    });

    $('#chatbot-input').keypress(function(e) {
        if (e.which == 13) {
            var userMessage = $(this).val();
            if (userMessage.trim() !== '') {
                $('#chatbot-messages').append('<div class="user-message">' + userMessage + '</div>');
                $(this).val('');

                // Send message to server
                $.ajax({
                    type: 'POST',
                    url: chatbot_ajax_object.ajax_url,
                    data: {
                        action: 'chatbot_message',
                        message: userMessage
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#chatbot-messages').append('<div class="bot-message">' + response.data + '</div>');
                        } else {
                            $('#chatbot-messages').append('<div class="bot-message">Error: ' + response.data + '</div>');
                        }
                    },
                    error: function() {
                        $('#chatbot-messages').append('<div class="bot-message">Error: Unable to communicate with server</div>');
                    }
                });
            }
        }
    });
});

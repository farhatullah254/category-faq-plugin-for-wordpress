(function($) {
    function showFaqs(category) {
        // Remove active class from all category headings
        $('.cfq-faq-categories h2').removeClass('active');

        // Add active class to the clicked category
        $('#toggle-' + category).addClass('active');

        // Hide all FAQ sections
        $('.cfq-faq').hide();

        // Show selected FAQ section
        const selectedFaqs = $('#' + category + '-faqs');
        if (selectedFaqs.length) {
            selectedFaqs.show();
        }
    }

    function toggleAnswer(element) {
        const answer = $(element).next('.cfq-faq-content');
        const isOpen = answer.is(':visible');

        // Toggle the clicked answer
        answer.toggle();
        $(element).find('.cfq-plus-icon').text(isOpen ? '+' : '-');
        $(element).toggleClass('active', !isOpen);
    }

    // Ensure DOM is fully loaded before running scripts
    $(document).ready(function() {
        showFaqs('general');

        // Add event listeners for FAQ category toggles
        $('.cfq-faq-categories h2').on('click', function() {
            showFaqs(this.id.replace('toggle-', ''));
        });

        // Add event listeners for FAQ answer toggles
        $(document).on('click', '.cfq-faq-item h3', function() {
            toggleAnswer(this);
        });
    });
})(jQuery);

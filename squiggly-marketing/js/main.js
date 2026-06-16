// Squiggly Marketing Theme JavaScript

(function($) {
    'use strict';
    
    // Mobile Navigation Toggle
    $('.mobile-toggle').on('click', function() {
        $('.nav').toggleClass('active');
    });
    
    // Smooth Scroll for Anchor Links
    $('a[href^="#"]').on('click', function(e) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 500);
        }
    });
    
    // Quiz Functionality
    if ($('.quiz-container').length) {
        var currentQuestion = 0;
        var answers = {};
        var questions = [
            {
                question: 'What is your monthly marketing budget?',
                options: [
                    { value: 'under200', label: 'Under $200' },
                    { value: '200-500', label: '$200-500' },
                    { value: '500-1000', label: '$500-1000' },
                    { value: '1000+', label: '$1000+' }
                ]
            },
            {
                question: 'What type of business do you have?',
                options: [
                    { value: 'restaurant', label: 'Restaurant / Food service' },
                    { value: 'retail', label: 'Retail store' },
                    { value: 'salon', label: 'Salon / Spa / Beauty' },
                    { value: 'professional', label: 'Professional services (lawyer, dentist, realtor)' },
                    { value: 'home', label: 'Home services (plumber, HVAC, landscaping)' },
                    { value: 'other', label: 'Other' }
                ]
            },
            {
                question: 'Do you have someone who can answer the phone when new customers call?',
                options: [
                    { value: 'yes', label: 'Yes - we answer our own phones' },
                    { value: 'sometimes', label: 'Sometimes' },
                    { value: 'no', label: 'No - we need help with that too' }
                ]
            },
            {
                question: "What's your goal?",
                options: [
                    { value: 'walkin', label: 'Get more walk-in customers' },
                    { value: 'leads', label: 'Generate more leads/calls' },
                    { value: 'presence', label: 'Build my online presence' },
                    { value: 'dontknow', label: "I don't know - that's why I'm asking" }
                ]
            },
            {
                question: 'How soon do you need results?',
                options: [
                    { value: 'yesterday', label: 'Yesterday (emergency)' },
                    { value: '30days', label: 'Within 30 days' },
                    { value: '90days', label: 'Within 90 days' },
                    { value: 'long', label: "I'm playing the long game" }
                ]
            }
        ];
        
        function renderQuestion(index) {
            var q = questions[index];
            var html = '<div class="quiz-question">';
            html += '<h3>' + q.question + '</h3>';
            html += '<div class="quiz-options">';
            
            q.options.forEach(function(opt, i) {
                html += '<div class="quiz-option" data-value="' + opt.value + '">' + opt.label + '</div>';
            });
            
            html += '</div></div>';
            html += '<div class="quiz-nav">';
            
            if (index > 0) {
                html += '<button class="btn btn-outline" id="quiz-prev">Back</button>';
            } else {
                html += '<div></div>';
            }
            
            html += '<button class="btn btn-primary" id="quiz-next" disabled>Next</button>';
            html += '</div>';
            
            $('.quiz-container').html(html);
            
            // Update progress bar
            var progress = ((index + 1) / questions.length) * 100;
            $('.quiz-progress-bar').css('width', progress + '%');
            
            // Option click handlers
            $('.quiz-option').on('click', function() {
                $('.quiz-option').removeClass('selected');
                $(this).addClass('selected');
                answers[questions[index].question] = $(this).data('value');
                $('#quiz-next').prop('disabled', false);
            });
            
            // Navigation handlers
            $('#quiz-next').on('click', function() {
                if (index < questions.length - 1) {
                    currentQuestion++;
                    renderQuestion(currentQuestion);
                } else {
                    showResults();
                }
            });
            
            $('#quiz-prev').on('click', function() {
                if (index > 0) {
                    currentQuestion--;
                    renderQuestion(currentQuestion);
                }
            });
        }
        
        function showResults() {
            var html = '<div class="quiz-results">';
            
            // Determine fit based on budget
            var budget = answers['What is your monthly marketing budget?'];
            
            if (budget === 'under200' || budget === '200-500') {
                html += '<h3>Thanks for Reaching Out!</h3>';
                html += '<p>For your budget, we\'d recommend our Starter tier. But let\'s still talk - no pressure.</p>';
            } else {
                html += '<h3>Looks Like We\'re a Great Match!</h3>';
                html += '<p>Based on your answers, our Growth or Dominance tier seems right. Let\'s talk.</p>';
            }
            
            html += '<a href="<?php echo home_url("/contact"); ?>" class="btn btn-primary">Schedule Your Free Strategy Call</a>';
            html += '</div>';
            
            $('.quiz-progress-bar').css('width', '100%');
            $('.quiz-container').html(html);
        }
        
        // Initialize
        renderQuestion(0);
    }
    
    // Sticky Header on Scroll
    $(window).on('scroll', function() {
        if ($(this).scrollTop() > 100) {
            $('.header').addClass('scrolled');
        } else {
            $('.header').removeClass('scrolled');
        }
    });
    
    // Animation on Scroll
    $(window).on('scroll', function() {
        $('.service-card, .problem-card, .step').each(function() {
            var position = $(this).offset().top;
            var scroll = $(window).scrollTop() + $(window).height();
            
            if (position < scroll - 100) {
                $(this).css('opacity', '1');
                $(this).css('transform', 'translateY(0)');
            }
        });
    });
    
    // Initialize animations
    $('.service-card, .problem-card, .step').css('opacity', '0');
    
})(jQuery);
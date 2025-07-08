/**
 * Font Awesome 5 Free - Icons
 * This is a minimal subset of Font Awesome icons used in the application
 * For production, consider using the official Font Awesome library
 */

(function() {
    // Create a style element
    const style = document.createElement('style');
    style.type = 'text/css';
    
    // Add Font Awesome CSS
    style.innerHTML = `
        @font-face {
            font-family: 'Font Awesome 5 Free';
            font-style: normal;
            font-weight: 900;
            src: url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-solid-900.woff2') format('woff2');
        }
        
        .fas {
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            -moz-osx-font-smoothing: grayscale;
            -webkit-font-smoothing: antialiased;
            display: inline-block;
            font-style: normal;
            font-variant: normal;
            text-rendering: auto;
            line-height: 1;
        }
        
        /* Icons used in the application */
        .fa-user:before { content: '\f007'; }
        .fa-robot:before { content: '\f544'; }
        .fa-file-upload:before { content: '\f574'; }
        .fa-paper-plane:before { content: '\f1d8'; }
        .fa-chart-bar:before { content: '\f080'; }
        .fa-chart-line:before { content: '\f201'; }
        .fa-chart-pie:before { content: '\f200'; }
        .fa-download:before { content: '\f019'; }
        .fa-question-circle:before { content: '\f059'; }
        .fa-cog:before { content: '\f013'; }
        .fa-sign-out-alt:before { content: '\f2f5'; }
        .fa-times:before { content: '\f00d'; }
    `;
    
    // Append the style element to the head
    document.head.appendChild(style);
})();
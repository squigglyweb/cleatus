console.log('TLN: External JS loaded');
jQuery(document).ready(function($) {
    console.log('TLN: jQuery ready');
    
    var allData = JSON.parse($('#tln-all-data').html());
    console.log('TLN: Data loaded, count:', allData.length);
    
    var perPage = 12;
    var currentPage = 1;
    
    var icons = {'Restaurant':'R','Cafe':'C','Bar':'B','Retail':'S','Services':'S','Food':'F','Health':'H','Auto':'A','Salon':'S','Fitness':'F','Hearing':'H','Nails':'N'};
    var placeholder = 'https://thelocalnearbuy.com/wp-content/uploads/2026/05/support-local-businesses.webp';
    var apiKey = tlnDir.apiKey;
    
    function renderPage(pageNum, data) {
        var start = (pageNum - 1) * perPage;
        var end = start + perPage;
        var items = data.slice(start, end);
        var grid = $('#tln-g');
        var pager = $('#tln-pager');
        
        if (items.length === 0) {
            grid.html('<p class="tln-no-results">No businesses match your search.</p>');
            pager.hide();
            $('#tln-count').text('Showing 0 businesses');
            return;
        }
        
        var html = '';
        for (var i = 0; i < items.length; i++) {
            var b = items[i];
            var icon = icons[b.cat] || '🏪';
            // Always use placeholder - tier check happens server-side in PHP
            var imgUrl = placeholder;
            
            html += '<div class="tln-card" data-n="'+b.name.toLowerCase()+'" data-c="'+b.cat+'" data-l="'+b.loc+'">';
            html += '<div class="tln-img-wrap"><img class="tln-img" src="'+imgUrl+'" alt="'+b.name+'" loading="lazy">';
            html += '<span class="tln-badge">'+b.loc.toUpperCase()+'</span></div>';
            html += '<div class="tln-content"><div class="tln-name-wrap"><h3 class="tln-name">'+b.name+'</h3></div>';
            html += '<div class="tln-cat">'+b.cat+' &bull; '+b.loc+'</div>';
            html += '<div class="tln-rating"><span class="tln-stars">'+'★'.repeat(Math.floor(b.rating))+'</span> <span class="tln-reviews">('+b.rating+')</span></div>';
            html += '<div class="tln-address">📍 '+b.addr+'</div>';
            html += '<a href="/profile/?biz='+encodeURIComponent(b.name)+'&pid='+b.place_id+'" class="tln-btn">View Profile</a>';
            html += '<div class="tln-claim-link"><a href="/claim/?biz='+encodeURIComponent(b.name)+'&pid='+b.place_id+'">Own this business? Claim it</a></div>';
            html += '</div></div>';
        }
        
        grid.html(html);
        $('#tln-count').text('Showing ' + items.length + ' of ' + data.length + ' businesses');
        
        var totalPages = Math.ceil(data.length / perPage);
        if (totalPages > 1) {
            var pageHtml = '';
            for (var i = 1; i <= totalPages; i++) {
                if (i === pageNum) {
                    pageHtml += '<span>'+i+'</span>';
                } else {
                    pageHtml += '<a href="#" class="tln-page-link" data-page="'+i+'">'+i+'</a>';
                }
            }
            pager.html(pageHtml);
            pager.show();
        } else {
            pager.hide();
        }
    }
    
    function filterData() {
        var q = $('#tln-s').val().toLowerCase();
        var c = $('#tln-c').val();
        var l = $('#tln-l').val();
        
        var filtered = [];
        for (var i = 0; i < allData.length; i++) {
            var x = allData[i];
            var matchQ = q === '' || x.name.toLowerCase().indexOf(q) > -1;
            var matchC = c === '' || x.cat === c;
            var matchL = l === '' || x.loc === l;
            if (matchQ && matchC && matchL) {
                filtered.push(x);
            }
        }
        
        currentPage = 1;
        renderPage(1, filtered);
    }
    
    $('#tln-s').on('input', filterData);
    $('#tln-c').on('change', filterData);
    $('#tln-l').on('change', filterData);
    
    $(document).on('click', '.tln-page-link', function(e) {
        e.preventDefault();
        var newPage = $(this).data('page');
        var q = $('#tln-s').val().toLowerCase();
        var c = $('#tln-c').val();
        var l = $('#tln-l').val();
        
        var filtered = [];
        for (var i = 0; i < allData.length; i++) {
            var x = allData[i];
            var matchQ = q === '' || x.name.toLowerCase().indexOf(q) > -1;
            var matchC = c === '' || x.cat === c;
            var matchL = l === '' || x.loc === l;
            if (matchQ && matchC && matchL) {
                filtered.push(x);
            }
        }
        
        renderPage(newPage, filtered);
    });
    
    // Initial render
    renderPage(1, allData);
});
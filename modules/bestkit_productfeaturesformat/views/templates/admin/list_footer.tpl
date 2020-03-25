
<div class="form-group">
    <div class="col-lg-12">
        <ul class="bestkit_productfeatures pagination pull-right">
            <li {if $bestkit_pfeatures_pagination.page <= 1}class="disabled"{/if}>
                <a href="javascript:void(0);" class="pagination-link" data-page="1" data-list-id="{$bestkit_pfeatures_pagination.list_id|escape:'htmlall':'UTF-8'}">
                    <i class="icon-double-angle-left"></i>
                </a>
            </li>
            <li {if $bestkit_pfeatures_pagination.page <= 1}class="disabled"{/if}>
                <a href="javascript:void(0);" class="pagination-link" data-page="{($bestkit_pfeatures_pagination.page - 1)|escape:'htmlall':'UTF-8'}" data-list-id="{$bestkit_pfeatures_pagination.list_id|escape:'htmlall':'UTF-8'}">
                    <i class="icon-angle-left"></i>
                </a>
            </li>
            {assign p 0}
            {while $p++ < $bestkit_pfeatures_pagination.total_pages}
                {if $p < $bestkit_pfeatures_pagination.page-2}
                    <li class="disabled">
                        <a href="javascript:void(0);">&hellip;</a>
                    </li>
                    {assign p $bestkit_pfeatures_pagination.page-3}
                {else if $p > $bestkit_pfeatures_pagination.page+2}
                    <li class="disabled">
                        <a href="javascript:void(0);">&hellip;</a>
                    </li>
                    {assign p $bestkit_pfeatures_pagination.total_pages}
                {else}
                    <li {if $p == $bestkit_pfeatures_pagination.page}class="active"{/if}>
                        <a href="javascript:void(0);" class="pagination-link" data-page="{$p|escape:'htmlall':'UTF-8'}" data-list-id="{$bestkit_pfeatures_pagination.list_id|escape:'htmlall':'UTF-8'}">{$p|escape:'htmlall':'UTF-8'}</a>
                    </li>
                {/if}
            {/while}
            <li {if $bestkit_pfeatures_pagination.page >= $bestkit_pfeatures_pagination.total_pages}class="disabled"{/if}>
                <a href="javascript:void(0);" class="pagination-link" data-page="{($bestkit_pfeatures_pagination.page + 1)|escape:'htmlall':'UTF-8'}" data-list-id="{$bestkit_pfeatures_pagination.list_id|escape:'htmlall':'UTF-8'}">
                    <i class="icon-angle-right"></i>
                </a>
            </li>
            <li {if $bestkit_pfeatures_pagination.page >= $bestkit_pfeatures_pagination.total_pages}class="disabled"{/if}>
                <a href="javascript:void(0);" class="pagination-link" data-page="{$bestkit_pfeatures_pagination.total_pages|escape:'htmlall':'UTF-8'}" data-list-id="{$bestkit_pfeatures_pagination.list_id|escape:'htmlall':'UTF-8'}">
                    <i class="icon-double-angle-right"></i>
                </a>
            </li>
        </ul>

        <div class="hidden_inputs">
            <input type="hidden" name="bestkit_productfeatures[page]" id="bestkit_productfeatures_page" value="{$bestkit_pfeatures_pagination.page|intval}" />

        </div>

        <script type="text/javascript">
            $('.bestkit_productfeatures .pagination-link').on('click',function(e){
                e.preventDefault();

                $.ajax({
                    type: 'POST',
                    headers: { "cache-control": "no-cache" },
                    url: admin_modules_link,
                    async: true,
                    cache: false,
                    dataType : "json",
                    data: 'configure=bestkit_productfeatures&action=productfeaturesPagination&ajax=true&page=' + $(this).data("page") + '&search=' + $input.val(),
                    beforeSend: function()	{
                        $('#pfeatures_container').css('opacity', '0.3')
                    },
                    success: function(jsonData)	{
                        $('#pfeatures_container').html(jsonData.html)
                        $('#pfeatures_container').css('opacity', '1')
                        $.scrollTo($('#pfeatures_container').position().top)
                    },
                    error: function()
                    {
                        $('#pfeatures_container').css('opacity', '1')
                    }
                });
            });


            var bestkit_pfeatures_typing_timer;
            var bestkit_pfeatures_done_typing_interval = 1500;
            var $input = $('#bestkit_productfeatures_search');
            //on keyup, start the countdown
            $input.on('keyup', function () {
                clearTimeout(bestkit_pfeatures_typing_timer);
                bestkit_pfeatures_typing_timer = setTimeout(bestkit_pfeatures_doneTyping, bestkit_pfeatures_done_typing_interval);
            });
            //on keydown, clear the countdown
            $input.on('keydown', function () {
                clearTimeout(bestkit_pfeatures_typing_timer);
            });

            function bestkit_pfeatures_doneTyping() {
                $.ajax({
                    type: 'POST',
                    headers: { "cache-control": "no-cache" },
                    url: admin_modules_link,
                    async: true,
                    cache: false,
                    dataType : "json",
                    data: 'configure=bestkit_productfeatures&action=productfeaturesSearch&ajax=true&search=' + $input.val(),
                    beforeSend: function()	{
                        $('#pfeatures_container').css('opacity', '0.3')
                    },
                    success: function(jsonData)	{
                        $('#pfeatures_container').html(jsonData.html)
                        $('#pfeatures_container').css('opacity', '1')
                        $.scrollTo($('#pfeatures_container').position().top)
                    },
                    error: function()
                    {
                        $('#pfeatures_container').css('opacity', '1')
                    }
                });
            }


        </script>
    </div>
</div>
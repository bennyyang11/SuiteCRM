{*
/**
 * Modern Dashlet Header Template
 * SuiteCRM Day 3: Mobile-Responsive Interface Implementation
 */
*}

<div onmouseover="this.style.cursor = 'move';" id="dashlet_header_{$DASHLET_ID}" class="modern-dashlet-header">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            {if $DASHLET_TITLE_ICON}
                <i class="{$DASHLET_TITLE_ICON} me-2"></i>
            {/if}
            <h3 class="mb-0">{$DASHLET_TITLE}</h3>
        </div>
        
        <div class="dashlet-actions d-flex gap-2">
            {if $DASHLET_BUTTON}
                <button type="button" class="btn btn-sm btn-outline-light" 
                        onclick="{$DASHLET_BUTTON.URL}" 
                        data-bs-toggle="tooltip" 
                        title="{$DASHLET_BUTTON.LABEL}">
                    <i class="fas fa-external-link-alt"></i>
                </button>
            {/if}
            
            {if $DASHLET_EDITURL}
                <button type="button" class="btn btn-sm btn-outline-light" 
                        onclick="SUGAR.mySugar.configureDashlet('{$DASHLET_ID}')" 
                        data-bs-toggle="tooltip" 
                        title="{$APP.LBL_EDIT}">
                    <i class="fas fa-cog"></i>
                </button>
            {/if}
            
            {if $DASHLET_REFRESHURL}
                <button type="button" class="btn btn-sm btn-outline-light" 
                        onclick="SUGAR.mySugar.retrieveDashlet('{$DASHLET_ID}')" 
                        data-bs-toggle="tooltip" 
                        title="{$APP.LBL_REFRESH}">
                    <i class="fas fa-sync-alt"></i>
                </button>
            {/if}
            
            {if $DASHLET_DELETEURL}
                <button type="button" class="btn btn-sm btn-outline-light" 
                        onclick="SUGAR.mySugar.deleteDashlet('{$DASHLET_ID}')" 
                        data-bs-toggle="tooltip" 
                        title="{$APP.LBL_DELETE}">
                    <i class="fas fa-times"></i>
                </button>
            {/if}
        </div>
    </div>
</div>

{literal}
<script>
// Initialize tooltips for this dashlet
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('#dashlet_header_{/literal}{$DASHLET_ID}{literal} [data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
{/literal}

<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once('include/MVC/View/SugarView.php');
require_once('modules/Manufacturing/Pipeline.php');

class ManufacturingViewOrderdashboard extends SugarView
{
    public function __construct()
    {
        parent::__construct();
    }

    public function display()
    {
        global $mod_strings, $current_user, $sugar_smarty;
        
        $pipeline = new Pipeline();
        $stages = array('Lead', 'Qualified', 'Proposal', 'Negotiation', 'Closed Won', 'Closed Lost');
        $orders_by_stage = array();
        
        foreach ($stages as $stage) {
            $orders_result = $pipeline->getOrdersByStage($stage);
            $orders_by_stage[$stage] = array();
            while ($row = $GLOBALS['db']->fetchByAssoc($orders_result)) {
                $orders_by_stage[$stage][] = $row;
            }
        }
        
        $summary = $pipeline->getStageSummary();
        
        echo '<div id="manufacturing-order-dashboard" class="container-fluid">';
        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<h2 class="page-header">Order Dashboard</h2>';
        echo '</div>';
        echo '</div>';
        
        // Summary Cards
        echo '<div class="row mb-4">';
        foreach ($stages as $stage) {
            $count = isset($summary[$stage]) ? $summary[$stage]['count'] : 0;
            $value = isset($summary[$stage]) ? number_format($summary[$stage]['total_value'], 0) : 0;
            
            echo '<div class="col-md-2">';
            echo '<div class="card text-center stage-summary">';
            echo '<div class="card-body">';
            echo '<h5 class="card-title">' . $stage . '</h5>';
            echo '<p class="card-text stage-count">' . $count . '</p>';
            echo '<p class="card-text stage-value">$' . $value . '</p>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        
        // Kanban Board
        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<div class="kanban-board">';
        
        foreach ($stages as $stage) {
            echo '<div class="kanban-column" data-stage="' . strtolower($stage) . '">';
            echo '<div class="kanban-header">';
            echo '<h4>' . $stage . '</h4>';
            echo '<span class="badge badge-secondary">' . count($orders_by_stage[$stage]) . '</span>';
            echo '</div>';
            echo '<div class="kanban-body">';
            
            foreach ($orders_by_stage[$stage] as $order) {
                echo '<div class="kanban-card" data-order-id="' . $order['order_id'] . '">';
                echo '<div class="card-header">';
                echo '<strong>' . htmlspecialchars($order['client_name']) . '</strong>';
                echo '</div>';
                echo '<div class="card-body">';
                echo '<p>' . htmlspecialchars(substr($order['description'], 0, 100)) . '...</p>';
                echo '<div class="order-meta">';
                echo '<span class="order-value">$' . number_format($order['value'], 0) . '</span>';
                echo '<span class="order-probability">' . $order['probability'] . '%</span>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Include CSS and JS for Kanban
        echo '<style>
        .kanban-board {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding: 20px 0;
        }
        .kanban-column {
            min-width: 300px;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }
        .kanban-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }
        .kanban-card {
            background: white;
            border-radius: 6px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.2s;
        }
        .kanban-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .kanban-card .card-header {
            background: #007bff;
            color: white;
            padding: 10px;
            border-radius: 6px 6px 0 0;
            font-size: 0.9em;
        }
        .kanban-card .card-body {
            padding: 15px;
        }
        .order-meta {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.9em;
        }
        .order-value {
            color: #28a745;
            font-weight: bold;
        }
        .order-probability {
            color: #007bff;
        }
        .stage-summary .card-body {
            padding: 15px 10px;
        }
        .stage-count {
            font-size: 2em;
            font-weight: bold;
            margin: 10px 0 5px 0;
        }
        .stage-value {
            font-size: 0.9em;
            color: #28a745;
        }
        </style>';
        
        echo '<script>
        $(document).ready(function() {
            // Make cards draggable (basic functionality)
            $(".kanban-card").draggable({
                helper: "clone",
                revert: "invalid"
            });
            
            $(".kanban-body").droppable({
                accept: ".kanban-card",
                drop: function(event, ui) {
                    var orderId = ui.helper.data("order-id");
                    var newStage = $(this).parent().data("stage");
                    
                    // Move card to new column
                    ui.helper.appendTo(this);
                    
                    // Update stage via AJAX
                    $.post("index.php?module=Manufacturing&action=updateStage", {
                        order_id: orderId,
                        new_stage: newStage
                    }, function(response) {
                        if (response.success) {
                            // Update counts and values
                            location.reload();
                        }
                    }, "json");
                }
            });
            
            // Card click for details
            $(".kanban-card").click(function() {
                var orderId = $(this).data("order-id");
                // Open order details modal or redirect
                window.open("index.php?module=Opportunities&action=DetailView&record=" + orderId, "_blank");
            });
        });
        </script>';
    }
}

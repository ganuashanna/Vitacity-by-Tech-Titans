<?php
/**
 * VITACITY - Urban Nervous System (UNS)
 * AI Engine with 4 Agents: Observer, Memory, Decision, Action
 * Team Tech Titan - SAMVED 2026
 */

require_once __DIR__ . '/database.php';

class UrbanNervousSystem {
    
    // Congestion thresholds
    const CONGESTION_LOW = 40;
    const CONGESTION_MEDIUM = 70;
    const CONGESTION_HIGH = 85;
    
    // ============================================================
    // AGENT 1: OBSERVER - Monitors real-time state
    // ============================================================
    
    public static function observe($zoneId = null) {
        $sql = "
            SELECT 
                z.id, z.name, z.total_slots, z.congestion_level,
                z.dynamic_price_per_hour,
                COUNT(CASE WHEN ps.state = 'available' THEN 1 END) as available_slots,
                COUNT(CASE WHEN ps.state = 'occupied' THEN 1 END) as occupied_slots
            FROM zones z
            LEFT JOIN parking_slots ps ON z.id = ps.zone_id
        ";
        
        if ($zoneId) {
            $sql .= " WHERE z.id = :zone_id";
            $params = ['zone_id' => $zoneId];
        } else {
            $params = [];
        }
        
        $sql .= " GROUP BY z.id, z.name, z.total_slots, z.congestion_level, z.dynamic_price_per_hour";
        
        $zones = Database::fetchAll($sql, $params);
        
        $observations = [];
        foreach ($zones as $zone) {
            $occupancy = ($zone['occupied_slots'] / $zone['total_slots']) * 100;
            
            $observations[] = [
                'zone_id' => $zone['id'],
                'zone_name' => $zone['name'],
                'total_slots' => $zone['total_slots'],
                'available' => $zone['available_slots'],
                'occupied' => $zone['occupied_slots'],
                'occupancy_percent' => round($occupancy, 2),
                'current_price' => $zone['dynamic_price_per_hour'],
                'congestion_level' => $zone['congestion_level'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        return $zoneId ? ($observations[0] ?? null) : $observations;
    }
    
    // ============================================================
    // AGENT 2: MEMORY - Recalls historical patterns
    // ============================================================
    
    public static function recall($zoneId, $dayOfWeek = null, $hourOfDay = null) {
        if ($dayOfWeek === null) {
            $dayOfWeek = (int)date('N') - 1; // 0 = Monday, 6 = Sunday
        }
        if ($hourOfDay === null) {
            $hourOfDay = (int)date('H');
        }
        
        // Get historical pattern for this time
        $sql = "
            SELECT 
                AVG(avg_occupancy) as avg_occupancy,
                AVG(total_bookings) as avg_bookings,
                AVG(total_complaints) as avg_complaints,
                MAX(pattern_strength) as pattern_strength
            FROM ai_learning_data
            WHERE zone_id = :zone_id
            AND day_of_week = :day_of_week
            AND hour_of_day = :hour_of_day
        ";
        
        $pattern = Database::fetchOne($sql, [
            'zone_id' => $zoneId,
            'day_of_week' => $dayOfWeek,
            'hour_of_day' => $hourOfDay
        ]);
        
        // Get complaint patterns
        $complaintSql = "
            SELECT 
                complaint_type,
                occurrence_count,
                pattern_detected,
                maintenance_required
            FROM complaint_patterns
            WHERE zone_id = :zone_id
            AND occurrence_count >= 2
            ORDER BY occurrence_count DESC
            LIMIT 5
        ";
        
        $complaints = Database::fetchAll($complaintSql, ['zone_id' => $zoneId]);
        
        return [
            'zone_id' => $zoneId,
            'day_of_week' => $dayOfWeek,
            'hour_of_day' => $hourOfDay,
            'historical_occupancy' => $pattern ? round($pattern['avg_occupancy'], 2) : null,
            'avg_bookings' => $pattern ? round($pattern['avg_bookings'], 0) : 0,
            'avg_complaints' => $pattern ? round($pattern['avg_complaints'], 0) : 0,
            'pattern_strength' => $pattern ? round($pattern['pattern_strength'], 4) : 0,
            'complaint_patterns' => $complaints
        ];
    }
    
    // ============================================================
    // AGENT 3: DECISION - Predicts future states
    // ============================================================
    
    public static function predict($zoneId, $hoursAhead = 2) {
        // OBSERVE current state
        $current = self::observe($zoneId);
        if (!$current) {
            return ['error' => 'Zone not found'];
        }
        
        $currentOccupancy = $current['occupancy_percent'];
        
        // RECALL historical pattern
        $targetTime = time() + ($hoursAhead * 3600);
        $targetDayOfWeek = (int)date('N', $targetTime) - 1;
        $targetHour = (int)date('H', $targetTime);
        
        $memory = self::recall($zoneId, $targetDayOfWeek, $targetHour);
        
        // PREDICT using historical pattern + current trend
        $predictedOccupancy = $memory['historical_occupancy'] ?? $currentOccupancy;
        
        // Adjust based on current trend
        $trendSql = "
            SELECT COUNT(*) as recent_bookings
            FROM bookings
            WHERE zone_id = :zone_id
            AND booked_at >= NOW() - INTERVAL '1 hour'
        ";
        $trend = Database::fetchOne($trendSql, ['zone_id' => $zoneId]);
        $recentBookings = $trend['recent_bookings'] ?? 0;
        
        // If high booking rate, increase prediction
        if ($recentBookings > 5) {
            $predictedOccupancy += ($recentBookings * 2);
        }
        
        // Check for complaint impact (sensor faults reduce capacity)
        $complaintImpactSql = "
            SELECT COUNT(DISTINCT slot_id) as affected_slots
            FROM complaints
            WHERE zone_id = :zone_id
            AND status IN ('open', 'in_progress')
            AND requires_maintenance = true
        ";
        $impact = Database::fetchOne($complaintImpactSql, ['zone_id' => $zoneId]);
        $affectedSlots = $impact['affected_slots'] ?? 0;
        
        // If slots are down, effective capacity is reduced
        if ($affectedSlots > 0) {
            $effectiveCapacity = $current['total_slots'] - $affectedSlots;
            if ($effectiveCapacity > 0) {
                $predictedOccupancy = ($current['occupied'] / $effectiveCapacity) * 100;
            }
        }
        
        // Cap predictions
        $predictedOccupancy = min(max($predictedOccupancy, 0), 100);
        
        // Determine congestion level
        if ($predictedOccupancy >= self::CONGESTION_HIGH) {
            $congestionLevel = 'High';
            $urgency = 'critical';
        } elseif ($predictedOccupancy >= self::CONGESTION_MEDIUM) {
            $congestionLevel = 'Medium';
            $urgency = 'warning';
        } else {
            $congestionLevel = 'Low';
            $urgency = 'normal';
        }
        
        // Calculate confidence based on data quality
        $confidence = 0.5; // Base confidence
        if ($memory['historical_occupancy']) {
            $confidence += 0.2; // Historical pattern exists
        }
        if ($recentBookings > 3) {
            $confidence += 0.15; // Recent trend data
        }
        if ($memory['pattern_strength'] > 0.7) {
            $confidence += 0.15; // Strong pattern
        }
        $confidence = min($confidence, 1.0);
        
        // Store prediction
        $predictionSql = "
            INSERT INTO ai_predictions (
                prediction_type, zone_id, prediction_for,
                confidence_score, predicted_occupancy_percent,
                predicted_congestion_level
            ) VALUES (
                'congestion', :zone_id, :prediction_for,
                :confidence, :occupancy, :congestion
            )
        ";
        
        $predictionFor = date('Y-m-d H:i:s', $targetTime);
        
        Database::execute($predictionSql, [
            'zone_id' => $zoneId,
            'prediction_for' => $predictionFor,
            'confidence' => $confidence,
            'occupancy' => $predictedOccupancy,
            'congestion' => $congestionLevel
        ]);
        
        return [
            'zone_id' => $zoneId,
            'prediction_for' => $predictionFor,
            'hours_ahead' => $hoursAhead,
            'current_occupancy' => $currentOccupancy,
            'predicted_occupancy' => round($predictedOccupancy, 2),
            'predicted_congestion' => $congestionLevel,
            'urgency' => $urgency,
            'confidence' => round($confidence, 4),
            'affected_slots' => $affectedSlots,
            'agent_analysis' => [
                'observer' => "Current: {$currentOccupancy}% occupied",
                'memory' => "Historical: " . ($memory['historical_occupancy'] ?? 'No data') . "% typical",
                'decision' => "Prediction: {$congestionLevel} congestion ({$predictedOccupancy}%)",
                'action_needed' => $urgency === 'critical'
            ]
        ];
    }
    
    // ============================================================
    // AGENT 4: ACTION - Takes autonomous actions
    // ============================================================
    
    public static function act($zoneId) {
        // Observe current state
        $state = self::observe($zoneId);
        if (!$state) {
            return ['error' => 'Zone not found'];
        }
        
        $occupancy = $state['occupancy_percent'];
        $actions = [];
        
        // ACTION 1: Send overflow alert
        if ($occupancy >= self::CONGESTION_HIGH) {
            $alertSql = "
                INSERT INTO alerts (
                    alert_type, severity, zone_id, title, message, status
                ) VALUES (
                    'overflow', 'critical', :zone_id, :title, :message, 'active'
                )
            ";
            
            Database::execute($alertSql, [
                'zone_id' => $zoneId,
                'title' => 'Zone Overflow Alert',
                'message' => "Zone {$state['zone_name']} at {$occupancy}% capacity - overflow risk"
            ]);
            
            $actions[] = [
                'action' => 'send_alert',
                'reason' => "Occupancy {$occupancy}% >= threshold " . self::CONGESTION_HIGH . "%",
                'status' => 'executed'
            ];
        }
        
        // ACTION 2: Adjust dynamic pricing
        if ($occupancy >= self::CONGESTION_MEDIUM) {
            $multiplier = $occupancy >= self::CONGESTION_HIGH ? 1.5 : 1.2;
            $baseSql = "SELECT base_price_per_hour FROM zones WHERE id = :zone_id";
            $base = Database::fetchOne($baseSql, ['zone_id' => $zoneId]);
            $newPrice = $base['base_price_per_hour'] * $multiplier;
            
            $priceSql = "
                UPDATE zones
                SET dynamic_price_per_hour = :price,
                    updated_at = NOW()
                WHERE id = :zone_id
            ";
            
            Database::execute($priceSql, [
                'price' => $newPrice,
                'zone_id' => $zoneId
            ]);
            
            $actions[] = [
                'action' => 'adjust_price',
                'reason' => "Demand-based pricing for {$occupancy}% occupancy",
                'old_price' => $state['current_price'],
                'new_price' => round($newPrice, 2),
                'multiplier' => $multiplier,
                'status' => 'executed'
            ];
        }
        
        // ACTION 3: Create maintenance tickets for complaint patterns
        $patternSql = "
            SELECT complaint_type, occurrence_count
            FROM complaint_patterns
            WHERE zone_id = :zone_id
            AND maintenance_required = true
            AND occurrence_count >= 3
        ";
        
        $patterns = Database::fetchAll($patternSql, ['zone_id' => $zoneId]);
        
        foreach ($patterns as $pattern) {
            // Check if ticket already exists
            $existsSql = "
                SELECT id FROM complaints
                WHERE zone_id = :zone_id
                AND type = :type
                AND ai_category = 'AI Generated'
                AND created_at >= NOW() - INTERVAL '1 day'
            ";
            
            $exists = Database::fetchOne($existsSql, [
                'zone_id' => $zoneId,
                'type' => $pattern['complaint_type']
            ]);
            
            if (!$exists) {
                $ticketSql = "
                    INSERT INTO complaints (
                        complaint_code, type, title, description,
                        priority, status, zone_id, ai_category
                    ) VALUES (
                        :code, :type, :title, :description,
                        'high', 'open', :zone_id, 'AI Generated'
                    )
                ";
                
                $code = 'MT-' . date('YmdHis');
                
                Database::execute($ticketSql, [
                    'code' => $code,
                    'type' => $pattern['complaint_type'],
                    'title' => "Auto: {$pattern['complaint_type']} pattern detected",
                    'description' => "AI detected recurring {$pattern['complaint_type']} ({$pattern['occurrence_count']} occurrences). Maintenance required.",
                    'zone_id' => $zoneId
                ]);
                
                $actions[] = [
                    'action' => 'create_maintenance_ticket',
                    'reason' => "{$pattern['occurrence_count']} recurring {$pattern['complaint_type']}",
                    'ticket_code' => $code,
                    'status' => 'executed'
                ];
            }
        }
        
        // ACTION 4: Suggest rerouting if overflow
        if ($occupancy >= self::CONGESTION_HIGH) {
            $alternativesSql = "
                SELECT 
                    z.id, z.name,
                    COUNT(CASE WHEN ps.state = 'available' THEN 1 END) as available,
                    z.total_slots
                FROM zones z
                LEFT JOIN parking_slots ps ON z.id = ps.zone_id
                WHERE z.id != :zone_id
                GROUP BY z.id
                HAVING (COUNT(CASE WHEN ps.state = 'available' THEN 1 END)::float / z.total_slots) > 0.4
                ORDER BY available DESC
                LIMIT 3
            ";
            
            $alternatives = Database::fetchAll($alternativesSql, ['zone_id' => $zoneId]);
            
            if (count($alternatives) > 0) {
                $actions[] = [
                    'action' => 'suggest_reroute',
                    'reason' => 'Alternative zones available',
                    'alternatives' => array_column($alternatives, 'id'),
                    'status' => 'suggested'
                ];
            }
        }
        
        // Log all actions
        foreach ($actions as $action) {
            $logSql = "
                INSERT INTO ai_actions (
                    action_type, zone_id, action_description,
                    triggered_by, status, executed_at
                ) VALUES (
                    :action_type, :zone_id, :description,
                    'uns_ai', 'executed', NOW()
                )
            ";
            
            Database::execute($logSql, [
                'action_type' => $action['action'],
                'zone_id' => $zoneId,
                'description' => $action['reason']
            ]);
        }
        
        return [
            'zone_id' => $zoneId,
            'timestamp' => date('Y-m-d H:i:s'),
            'occupancy' => $occupancy,
            'actions_taken' => count($actions),
            'actions' => $actions
        ];
    }
    
    // ============================================================
    // DETECT ANOMALIES
    // ============================================================
    
    public static function detectAnomalies($zoneId) {
        $anomalies = [];
        
        // 1. Phantom vehicles (sensor says occupied but no booking)
        $phantomSql = "
            SELECT ps.id, ps.slot_number
            FROM parking_slots ps
            WHERE ps.zone_id = :zone_id
            AND ps.state = 'occupied'
            AND ps.sensor_status = true
            AND NOT EXISTS (
                SELECT 1 FROM bookings b
                WHERE b.slot_id = ps.id
                AND b.status = 'active'
            )
        ";
        
        $phantoms = Database::fetchAll($phantomSql, ['zone_id' => $zoneId]);
        
        foreach ($phantoms as $p) {
            $anomalies[] = [
                'type' => 'phantom_vehicle',
                'severity' => 'warning',
                'slot_id' => $p['id'],
                'message' => "Slot {$p['slot_number']} shows occupied but no booking exists",
                'action' => 'verify_sensor'
            ];
        }
        
        // 2. Sensor faults (multiple complaints)
        $faultSql = "
            SELECT slot_id, COUNT(*) as count
            FROM complaints
            WHERE zone_id = :zone_id
            AND type IN ('Sensor Fault', 'QR Unreadable')
            AND created_at >= NOW() - INTERVAL '7 days'
            AND status IN ('open', 'in_progress')
            GROUP BY slot_id
            HAVING COUNT(*) >= 2
        ";
        
        $faults = Database::fetchAll($faultSql, ['zone_id' => $zoneId]);
        
        foreach ($faults as $f) {
            $anomalies[] = [
                'type' => 'sensor_fault',
                'severity' => $f['count'] >= 3 ? 'critical' : 'warning',
                'slot_id' => $f['slot_id'],
                'message' => "Sensor issue - {$f['count']} complaints in 7 days",
                'action' => 'create_maintenance_ticket'
            ];
        }
        
        return [
            'zone_id' => $zoneId,
            'timestamp' => date('Y-m-d H:i:s'),
            'anomalies_detected' => count($anomalies),
            'anomalies' => $anomalies
        ];
    }
}
?>
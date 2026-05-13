<?php
// Gamification and Engagement System
class GamificationSystem {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    /**
     * Award achievement to user
     */
    public function awardAchievement($user_id, $achievement_name) {
        // Get achievement details
        $stmt = $this->conn->prepare('SELECT id, points FROM achievements WHERE name = ?');
        $stmt->bind_param('s', $achievement_name);
        $stmt->execute();
        $achievement = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$achievement) return false;
        
        // Check if already awarded
        $stmt = $this->conn->prepare('SELECT id FROM user_achievements WHERE user_id = ? AND achievement_id = ?');
        $stmt->bind_param('ii', $user_id, $achievement['id']);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->close();
            return false; // Already awarded
        }
        $stmt->close();
        
        // Award achievement
        $stmt = $this->conn->prepare('INSERT INTO user_achievements (user_id, achievement_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $user_id, $achievement['id']);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            // Log activity
            $this->logActivity($user_id, 'achievement_earned', 'achievement', $achievement['id']);
            return $achievement['points'];
        }
        
        return false;
    }
    
    /**
     * Check and award achievements based on user activity
     */
    public function checkAchievements($user_id) {
        $achievements_awarded = [];
        
        // Check First Application
        $stmt = $this->conn->prepare('SELECT COUNT(*) as count FROM applications WHERE student_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $app_count = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
        
        if ($app_count >= 1) {
            $points = $this->awardAchievement($user_id, 'First Application');
            if ($points) $achievements_awarded[] = ['name' => 'First Application', 'points' => $points];
        }
        
        if ($app_count >= 5) {
            $points = $this->awardAchievement($user_id, 'Active Seeker');
            if ($points) $achievements_awarded[] = ['name' => 'Active Seeker', 'points' => $points];
        }
        
        // Check Profile Completion
        $completion = $this->getProfileCompletion($user_id);
        if ($completion >= 100) {
            $points = $this->awardAchievement($user_id, 'Profile Complete');
            if ($points) $achievements_awarded[] = ['name' => 'Profile Complete', 'points' => $points];
        }
        
        // Check Skills
        $stmt = $this->conn->prepare('SELECT COUNT(*) as count FROM student_skills WHERE student_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $skills_count = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
        
        if ($skills_count >= 10) {
            $points = $this->awardAchievement($user_id, 'Skill Master');
            if ($points) $achievements_awarded[] = ['name' => 'Skill Master', 'points' => $points];
        }
        
        // Check Interviews
        $stmt = $this->conn->prepare('SELECT COUNT(*) as count FROM interviews WHERE student_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $interview_count = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
        
        if ($interview_count >= 1) {
            $points = $this->awardAchievement($user_id, 'Interview Ready');
            if ($points) $achievements_awarded[] = ['name' => 'Interview Ready', 'points' => $points];
        }
        
        // Check Early Bird (applied within first week)
        $stmt = $this->conn->prepare('
            SELECT COUNT(*) as count FROM applications a 
            JOIN internships i ON a.internship_id = i.id 
            WHERE a.student_id = ? AND DATEDIFF(a.created_at, i.created_at) <= 7
        ');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $early_count = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
        
        if ($early_count >= 1) {
            $points = $this->awardAchievement($user_id, 'Early Bird');
            if ($points) $achievements_awarded[] = ['name' => 'Early Bird', 'points' => $points];
        }
        
        return $achievements_awarded;
    }
    
    /**
     * Get user's total points
     */
    public function getUserPoints($user_id) {
        $stmt = $this->conn->prepare('
            SELECT SUM(a.points) as total_points 
            FROM user_achievements ua 
            JOIN achievements a ON ua.achievement_id = a.id 
            WHERE ua.user_id = ?
        ');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result['total_points'] ?? 0;
    }
    
    /**
     * Get user's achievements
     */
    public function getUserAchievements($user_id) {
        $stmt = $this->conn->prepare('
            SELECT a.*, ua.earned_at 
            FROM user_achievements ua 
            JOIN achievements a ON ua.achievement_id = a.id 
            WHERE ua.user_id = ? 
            ORDER BY ua.earned_at DESC
        ');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $achievements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $achievements;
    }
    
    /**
     * Get leaderboard
     */
    public function getLeaderboard($limit = 10) {
        $stmt = $this->conn->prepare('
            SELECT u.name, u.role, SUM(a.points) as total_points,
                   COUNT(ua.achievement_id) as achievements_count
            FROM user_achievements ua 
            JOIN achievements a ON ua.achievement_id = a.id 
            JOIN users u ON ua.user_id = u.id 
            GROUP BY ua.user_id 
            ORDER BY total_points DESC 
            LIMIT ?
        ');
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $leaderboard = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $leaderboard;
    }
    
    /**
     * Calculate profile completion percentage
     */
    public function getProfileCompletion($user_id) {
        $completion = 0;
        
        // Get user info
        $stmt = $this->conn->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$user) return 0;
        
        if ($user['role'] === 'student') {
            // Check student profile
            $stmt = $this->conn->prepare('SELECT * FROM student_profiles WHERE user_id = ?');
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $profile = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($profile) {
                // Basic info (20%)
                if (!empty($profile['bio'])) $completion += 5;
                if (!empty($profile['university'])) $completion += 5;
                if (!empty($profile['location'])) $completion += 5;
                if (!empty($profile['phone'])) $completion += 5;
                
                // Academic info (20%)
                if ($profile['gpa'] > 0) $completion += 10;
                if (!empty($profile['graduation_year'])) $completion += 10;
                
                // Professional info (30%)
                if (!empty($profile['resume_path'])) $completion += 15;
                if (!empty($profile['linkedin_url'])) $completion += 10;
                if (!empty($profile['portfolio_url'])) $completion += 5;
                
                // Skills (30%)
                $stmt = $this->conn->prepare('SELECT COUNT(*) as count FROM student_skills WHERE student_id = ?');
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $skills_count = $stmt->get_result()->fetch_assoc()['count'];
                $stmt->close();
                
                $completion += min($skills_count * 3, 30); // 3 points per skill, max 30
            }
        } elseif ($user['role'] === 'company') {
            // Check company profile
            $stmt = $this->conn->prepare('SELECT * FROM company_profiles WHERE user_id = ?');
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $profile = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($profile) {
                // Basic info (30%)
                if (!empty($profile['company_name'])) $completion += 10;
                if (!empty($profile['description'])) $completion += 10;
                if (!empty($profile['location'])) $completion += 10;
                
                // Professional info (40%)
                if (!empty($profile['website'])) $completion += 10;
                if (!empty($profile['industry'])) $completion += 10;
                if (!empty($profile['company_size'])) $completion += 10;
                if (!empty($profile['founded_year'])) $completion += 10;
                
                // Branding (30%)
                if (!empty($profile['logo_path'])) $completion += 15;
                if (!empty($profile['social_links'])) $completion += 15;
            }
        }
        
        // Update profile completion in database
        $stmt = $this->conn->prepare('
            UPDATE student_profiles SET profile_complete_percentage = ? WHERE user_id = ?
        ');
        $stmt->bind_param('ii', $completion, $user_id);
        $stmt->execute();
        $stmt->close();
        
        return min($completion, 100);
    }
    
    /**
     * Get user level based on points
     */
    public function getUserLevel($user_id) {
        $points = $this->getUserPoints($user_id);
        
        if ($points < 50) return ['level' => 1, 'title' => 'Beginner', 'next_points' => 50];
        if ($points < 150) return ['level' => 2, 'title' => 'Explorer', 'next_points' => 150];
        if ($points < 300) return ['level' => 3, 'title' => 'Achiever', 'next_points' => 300];
        if ($points < 500) return ['level' => 4, 'title' => 'Professional', 'next_points' => 500];
        if ($points < 1000) return ['level' => 5, 'title' => 'Expert', 'next_points' => 1000];
        
        return ['level' => 6, 'title' => 'Master', 'next_points' => $points];
    }
    
    /**
     * Get engagement statistics
     */
    public function getEngagementStats($user_id) {
        $stats = [];
        
        // Applications count
        $stmt = $this->conn->prepare('SELECT COUNT(*) as count FROM applications WHERE student_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stats['applications'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
        
        // Interviews count
        $stmt = $this->conn->prepare('SELECT COUNT(*) as count FROM interviews WHERE student_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stats['interviews'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
        
        // Skills count
        $stmt = $this->conn->prepare('SELECT COUNT(*) as count FROM student_skills WHERE student_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stats['skills'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
        
        // Bookmarks count
        $stmt = $this->conn->prepare('SELECT COUNT(*) as count FROM bookmarks WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stats['bookmarks'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
        
        // Messages sent
        $stmt = $this->conn->prepare('SELECT COUNT(*) as count FROM messages WHERE sender_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stats['messages_sent'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
        
        return $stats;
    }
    
    /**
     * Log user activity
     */
    private function logActivity($user_id, $activity_type, $resource_type = null, $resource_id = null) {
        $stmt = $this->conn->prepare('
            INSERT INTO user_activity (user_id, activity_type, resource_type, resource_id) 
            VALUES (?, ?, ?, ?)
        ');
        $stmt->bind_param('issi', $user_id, $activity_type, $resource_type, $resource_id);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Get available achievements
     */
    public function getAvailableAchievements() {
        $stmt = $this->conn->prepare('SELECT * FROM achievements ORDER BY points ASC');
        $stmt->execute();
        $achievements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $achievements;
    }
}
?>

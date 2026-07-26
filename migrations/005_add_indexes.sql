ALTER TABLE users
    ADD INDEX idx_users_role_status (role, status),
    ADD INDEX idx_users_subscription_status (subscription_status);

ALTER TABLE profiles
    ADD INDEX idx_profiles_type_visible (type, is_visible),
    ADD INDEX idx_profiles_location (location),
    ADD INDEX idx_profiles_rate (hourly_rate);

ALTER TABLE offers
    ADD INDEX idx_offers_entreprise_status (entreprise_id, status),
    ADD INDEX idx_offers_type_status (type, status),
    ADD INDEX idx_offers_category (category_id),
    ADD INDEX idx_offers_location (location);

ALTER TABLE applications
    ADD INDEX idx_applications_offer_status (offer_id, status),
    ADD INDEX idx_applications_entreprise_status (entreprise_id, status);

ALTER TABLE subscriptions
    ADD INDEX idx_subscriptions_user_status (user_id, status),
    ADD INDEX idx_subscriptions_renews_at (renews_at);

ALTER TABLE messages
    ADD INDEX idx_messages_conversation_created (conversation_id, created_at),
    ADD INDEX idx_messages_receiver_read (receiver_id, read_at);

ALTER TABLE notifications
    ADD INDEX idx_notifications_user_read (user_id, read_at);

ALTER TABLE matching_cache
    ADD INDEX idx_matching_cache_expires_at (expires_at);

ALTER TABLE saved_searches
    ADD INDEX idx_saved_searches_user_alert (user_id, alert_enabled);
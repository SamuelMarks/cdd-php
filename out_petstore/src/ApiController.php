<?php

class ApiController {
    /**
     * Update an existing pet.
     *
     * Update an existing pet by Id.
     *
     * @tags pet
     */
    public function updatePet(Pet $body): Pet {
        // Implementation
    }

    /**
     * Add a new pet to the store.
     *
     * Add a new pet to the store.
     *
     * @tags pet
     */
    public function addPet(Pet $body): Pet {
        // Implementation
    }

    /**
     * Finds Pets by status.
     *
     * Multiple status values can be provided with comma separated strings.
     *
     * @tags pet
     */
    public function findPetsByStatus(string $status): array {
        // Implementation
    }

    /**
     * Finds Pets by tags.
     *
     * Multiple tags can be provided with comma separated strings. Use         tag1, tag2, tag3 for testing.
     *
     * @tags pet
     */
    public function findPetsByTags(array $tags): array {
        // Implementation
    }

    /**
     * Find pet by ID.
     *
     * Returns a single pet.
     *
     * @tags pet
     */
    public function getPetById(int $petId): Pet {
        // Implementation
    }

    /**
     * Updates a pet in the store with form data.
     *
     * Updates a pet resource based on the form data.
     *
     * @tags pet
     */
    public function updatePetWithForm(int $petId, ?string $name, ?string $status): Pet {
        // Implementation
    }

    /**
     * Deletes a pet.
     *
     * Delete a pet.
     *
     * @tags pet
     */
    public function deletePet(?string $api_key, int $petId) {
        // Implementation
    }

    /**
     * Uploads an image.
     *
     * Upload image of the pet.
     *
     * @tags pet
     */
    public function uploadFile(int $petId, ?string $additionalMetadata, $body): ApiResponse {
        // Implementation
    }

    /**
     * Returns pet inventories by status.
     *
     * Returns a map of status codes to quantities.
     *
     * @tags store
     */
    public function getInventory(): object {
        // Implementation
    }

    /**
     * Place an order for a pet.
     *
     * Place a new order in the store.
     *
     * @tags store
     */
    public function placeOrder(?Order $body): Order {
        // Implementation
    }

    /**
     * Find purchase order by ID.
     *
     * For valid response try integer IDs with value <= 5 or > 10. Other values will generate exceptions.
     *
     * @tags store
     */
    public function getOrderById(int $orderId): Order {
        // Implementation
    }

    /**
     * Delete purchase order by identifier.
     *
     * For valid response try integer IDs with value < 1000. Anything above 1000 or non-integers will generate API errors.
     *
     * @tags store
     */
    public function deleteOrder(int $orderId) {
        // Implementation
    }

    /**
     * Create user.
     *
     * This can only be done by the logged in user.
     *
     * @tags user
     */
    public function createUser(?User $body): User {
        // Implementation
    }

    /**
     * Creates list of users with given input array.
     *
     * Creates list of users with given input array.
     *
     * @tags user
     */
    public function createUsersWithListInput(?array $body): User {
        // Implementation
    }

    /**
     * Logs user into the system.
     *
     * Log into the system.
     *
     * @tags user
     */
    public function loginUser(?string $username, ?string $password): string {
        // Implementation
    }

    /**
     * Logs out current logged in user session.
     *
     * Log user out of the system.
     *
     * @tags user
     */
    public function logoutUser() {
        // Implementation
    }

    /**
     * Get user by user name.
     *
     * Get user detail based on username.
     *
     * @tags user
     */
    public function getUserByName(string $username): User {
        // Implementation
    }

    /**
     * Update user resource.
     *
     * This can only be done by the logged in user.
     *
     * @tags user
     */
    public function updateUser(string $username, ?User $body) {
        // Implementation
    }

    /**
     * Delete user resource.
     *
     * This can only be done by the logged in user.
     *
     * @tags user
     */
    public function deleteUser(string $username) {
        // Implementation
    }

}

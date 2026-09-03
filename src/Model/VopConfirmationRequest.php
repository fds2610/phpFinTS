<?php

namespace Fhp\Model;

/**
 * Provides information (about the payee) that the client application should present to the user and then ask for their
 * confirmation that the transfer (to this payee) should be executed.
 */
interface VopConfirmationRequest
{
    /** An HTML-formatted text that (if present) the application must show to the user when asking for confirmation. */
    public function getInformationForUser(): ?string;

    /** If this returns a non-null value, the confirmation request is only valid up to that time. */
    public function getExpiration(): ?\DateTime;

    /** The main outcome of the payee verification. See {@link VopVerificationResult} for possible values. */
    public function getVerificationResult(): ?string;

    /**
     * If {@link getVerificationResult()} returns {@link VopVerificationResult::NotApplicable}, then this function MAY
     * return an additional explanation (in the user's language or in English), but it may also return null.
     */
    public function getVerificationNotApplicableReason(): ?string;

    /**
     * The payee name that the payee's bank has on file, which the bank offers as a decision aid when the name we asked
     * for is a close match ({@link VopVerificationResult::CompletedCloseMatch}).
     *
     * The specification only allows banks to disclose it in that case, so this returns null for all other results, and
     * it may be null even for a close match if the bank sends the result as a pain.002 message.
     */
    public function getDifferingPayeeName(): ?string;
}

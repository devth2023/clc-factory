from enum import IntEnum, Enum


class BitPosition(IntEnum):
    IS_ACTIVE = 0
    IS_VIP = 1
    IS_VERIFIED = 2
    IS_BANNED = 3
    IS_PREMIUM = 4
    IS_2FA_ENABLED = 5
    EMAIL_CONFIRMED = 6
    PHONE_CONFIRMED = 7

    CAN_READ = 8
    CAN_WRITE = 9
    CAN_DELETE = 10
    CAN_ADMIN = 11
    CAN_PUBLISH = 12
    CAN_MODERATE = 13
    CAN_VERIFY_OTHERS = 14
    CAN_EXPORT = 15

    ROLE_USER = 16
    ROLE_MODERATOR = 17
    ROLE_ADMIN = 18
    ROLE_SYSTEM = 19
    ROLE_BOT = 20
    ROLE_GUEST = 21

    HAS_PROFILE_PICTURE = 24
    HAS_BIO = 25
    HAS_VERIFIED_EMAIL = 26
    HAS_STRIPE_PAYMENT = 27
    HAS_OAUTH_LOGIN = 28

    IS_PUBLISHED = 32
    IS_ARCHIVED = 33
    IS_DELETED = 34
    IS_LOCKED = 35
    IS_REVIEWED = 36
    IS_FEATURED = 37
    IS_SPONSORED = 38
    NEEDS_APPROVAL = 39

    HAS_COMMENTS = 40
    HAS_ATTACHMENTS = 41
    IS_PUBLIC = 42
    IS_SHARED = 43
    REQUIRES_AUTH = 44
    HAS_EXPIRY = 45
    IS_SENSITIVE = 46
    HAS_ENCRYPTION = 47

    def mask(self) -> int:
        return 1 << self.value

    @staticmethod
    def is_valid(position: int) -> bool:
        return 0 <= position <= 63


class CallerType(IntEnum):
    BOT = 0x0100
    AUTHENTICATED = 0x0200
    ATTACKER = 0x0400

    def matches(self, mask: int) -> bool:
        return (mask & self.value) == self.value

    def label(self) -> str:
        return {
            0x0100: "SEO Bot",
            0x0200: "Authenticated User",
            0x0400: "Unknown Caller",
        }.get(self.value, "Unknown Type")


class ProjectionType(str, Enum):
    GLOSSARY = "glossary"
    PRIVATE = "private"
    DECEPTION = "deception"

    def label(self) -> str:
        return {
            "glossary": "Public Glossary",
            "private": "Private Data",
            "deception": "Honeypot",
        }.get(self.value, "Unknown")

from clc.enums import CallerType


class CallerDetector:
    SEO_BOT_PATTERNS = [
        "googlebot",
        "bingbot",
        "slurp",
        "duckduckbot",
        "baiduspider",
        "yandexbot",
        "facebookexternalhit",
        "twitterbot",
        "linkedinbot",
        "whatsapp",
        "slackchannel",
        "pinterestbot",
    ]

    def detect(self, user_agent: str = "", auth_token: str = "") -> int:
        if self._is_seo_bot(user_agent):
            return CallerType.BOT.value

        if self._is_authenticated(auth_token):
            return CallerType.AUTHENTICATED.value

        return CallerType.ATTACKER.value

    def get_label(self, caller_mask: int) -> str:
        if caller_mask == CallerType.BOT.value:
            return "SEO Bot"
        elif caller_mask == CallerType.AUTHENTICATED.value:
            return "Authenticated User"
        else:
            return "Unknown Caller"

    def _is_seo_bot(self, user_agent: str) -> bool:
        user_agent_lower = user_agent.lower()
        return any(pattern in user_agent_lower for pattern in self.SEO_BOT_PATTERNS)

    def _is_authenticated(self, auth_token: str) -> bool:
        if not auth_token:
            return False

        import re

        pattern = r"^(Bearer|Token)\s+\S+$"
        return bool(re.match(pattern, auth_token, re.IGNORECASE))

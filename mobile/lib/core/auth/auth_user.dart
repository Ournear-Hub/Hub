class AuthUser {
  const AuthUser({
    required this.name,
    required this.username,
    required this.role,
    required this.emailVerified,
    required this.mustChangePassword,
    this.profileCompletion,
  });

  final String name;
  final String username;
  final String role;
  final bool emailVerified;
  final bool mustChangePassword;

  /// Server profile completion percent from the account payload, when present.
  final int? profileCompletion;

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    final rawCompletion = json['profile_completion'];
    final completion = rawCompletion is int
        ? rawCompletion
        : int.tryParse('$rawCompletion');
    return AuthUser(
      name: json['name'] as String? ?? '',
      username: json['username'] as String? ?? '',
      role: json['role'] as String? ?? '',
      emailVerified: json['email_verified'] as bool? ?? false,
      mustChangePassword: json['must_change_password'] as bool? ?? false,
      profileCompletion: completion,
    );
  }
}

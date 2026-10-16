class PublicJob {
  const PublicJob({
    required this.title,
    required this.slug,
    this.project,
    this.companyName,
    this.companyLogoUrl,
    this.industry,
    this.category,
    this.location,
    this.employmentType,
    this.experience,
    this.salary,
    this.postedLabel,
    this.closingLabel,
    this.featured = false,
    this.urgent = false,
    this.isClosed = false,
    this.isSaved,
  });

  final String title;
  final String slug;
  final String? project;
  final String? companyName;
  final String? companyLogoUrl;
  final String? industry;
  final String? category;
  final String? location;
  final String? employmentType;
  final String? experience;
  final String? salary;
  final String? postedLabel;
  final String? closingLabel;
  final bool featured;
  final bool urgent;
  final bool isClosed;

  /// Present only when the API includes saved-state for the authenticated viewer.
  final bool? isSaved;

  factory PublicJob.fromJson(Map<String, dynamic> json) {
    return PublicJob(
      title: json['title'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      project: json['project'] as String?,
      companyName: json['company_name'] as String?,
      companyLogoUrl: json['company_logo_url'] as String?,
      industry: json['industry'] as String?,
      category: json['category'] as String?,
      location: json['location'] as String?,
      employmentType: json['employment_type'] as String?,
      experience: json['experience'] as String?,
      salary: json['salary'] as String?,
      postedLabel: json['posted_label'] as String?,
      closingLabel: json['closing_label'] as String?,
      featured: json['featured'] as bool? ?? false,
      urgent: json['urgent'] as bool? ?? false,
      isClosed: json['is_closed'] as bool? ?? false,
      isSaved: json.containsKey('is_saved')
          ? json['is_saved'] as bool? ?? false
          : null,
    );
  }

  PublicJob copyWithSaved(bool? isSaved) {
    return PublicJob(
      title: title,
      slug: slug,
      project: project,
      companyName: companyName,
      companyLogoUrl: companyLogoUrl,
      industry: industry,
      category: category,
      location: location,
      employmentType: employmentType,
      experience: experience,
      salary: salary,
      postedLabel: postedLabel,
      closingLabel: closingLabel,
      featured: featured,
      urgent: urgent,
      isClosed: isClosed,
      isSaved: isSaved,
    );
  }

  String get subtitle {
    return [location, employmentType, experience].where((part) => part != null && part.trim().isNotEmpty).join(' · ');
  }
}

class PublicJobDetail extends PublicJob {
  const PublicJobDetail({
    required super.title,
    required super.slug,
    super.project,
    super.companyName,
    super.companyLogoUrl,
    super.industry,
    super.category,
    super.location,
    super.employmentType,
    super.experience,
    super.salary,
    super.postedLabel,
    super.closingLabel,
    super.featured,
    super.urgent,
    super.isClosed,
    super.isSaved,
    this.description,
    this.responsibilities,
    this.requirements,
    this.whatWeOffer,
    this.acceptsApplications,
  });

  final String? description;
  final String? responsibilities;
  final String? requirements;
  final String? whatWeOffer;
  final bool? acceptsApplications;

  factory PublicJobDetail.fromJson(Map<String, dynamic> json) {
    return PublicJobDetail(
      title: json['title'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      project: json['project'] as String?,
      companyName: json['company_name'] as String?,
      companyLogoUrl: json['company_logo_url'] as String?,
      industry: json['industry'] as String?,
      category: json['category'] as String?,
      location: json['location'] as String?,
      employmentType: json['employment_type'] as String?,
      experience: json['experience'] as String?,
      salary: json['salary'] as String?,
      postedLabel: json['posted_label'] as String?,
      closingLabel: json['closing_label'] as String?,
      featured: json['featured'] as bool? ?? false,
      urgent: json['urgent'] as bool? ?? false,
      isClosed: json['is_closed'] as bool? ?? false,
      isSaved: json.containsKey('is_saved')
          ? json['is_saved'] as bool? ?? false
          : null,
      description: json['description'] as String?,
      responsibilities: json['responsibilities'] as String?,
      requirements: json['requirements'] as String?,
      whatWeOffer: json['what_we_offer'] as String?,
      acceptsApplications: json['accepts_applications'] as bool?,
    );
  }
}

class PaginatedPublicJobs {
  const PaginatedPublicJobs({
    required this.jobs,
    required this.currentPage,
    required this.lastPage,
    required this.total,
    this.nextPageUrl,
  });

  final List<PublicJob> jobs;
  final int currentPage;
  final int lastPage;
  final int total;
  final String? nextPageUrl;

  factory PaginatedPublicJobs.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as List<dynamic>? ?? [];
    final meta = json['meta'] as Map<String, dynamic>? ?? {};
    final links = json['links'] as Map<String, dynamic>? ?? {};

    return PaginatedPublicJobs(
      jobs: data.map((item) => PublicJob.fromJson(item as Map<String, dynamic>)).toList(),
      currentPage: meta['current_page'] as int? ?? 1,
      lastPage: meta['last_page'] as int? ?? 1,
      total: meta['total'] as int? ?? data.length,
      nextPageUrl: links['next'] as String?,
    );
  }

  bool get hasMore => currentPage < lastPage;
}

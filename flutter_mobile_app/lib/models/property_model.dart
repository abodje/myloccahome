class PropertyModel {
  final int id;
  final String reference;
  final String name;
  final String address;
  final String city;
  final String postalCode;
  final String fullAddress;
  final String type;
  final int? surface;
  final int? rooms;
  final int? bedrooms;
  final int? bathrooms;
  final int? toilets;
  final int? floor;
  final int? totalFloors;
  final int? balconies;
  final double? terraceSurface;
  final double? gardenSurface;
  final double? landSurface;
  final int? parkingSpaces;
  final int? garageSpaces;
  final double? cellarSurface;
  final double? atticSurface;
  final int? constructionYear;
  final int? renovationYear;
  final String? heatingType;
  final String? hotWaterType;
  final String? energyClass;
  final int? energyConsumption;
  final String? orientation;
  final bool furnished;
  final bool petsAllowed;
  final bool smokingAllowed;
  final bool elevator;
  final bool hasBalcony;
  final bool hasParking;
  final bool airConditioning;
  final bool heating;
  final bool hotWater;
  final bool internet;
  final bool cable;
  final bool dishwasher;
  final bool washingMachine;
  final bool dryer;
  final bool refrigerator;
  final bool oven;
  final bool microwave;
  final bool stove;
  final String? description;
  final List<String>? equipmentList;
  final String? country;
  final String? region;
  final String? district;
  final double? latitude;
  final double? longitude;
  final List<String>? photos;
  final String? videoUrl;

  PropertyModel({
    required this.id,
    required this.reference,
    required this.name,
    required this.address,
    required this.city,
    required this.postalCode,
    required this.fullAddress,
    required this.type,
    this.surface,
    this.rooms,
    this.bedrooms,
    this.bathrooms,
    this.toilets,
    this.floor,
    this.totalFloors,
    this.balconies,
    this.terraceSurface,
    this.gardenSurface,
    this.landSurface,
    this.parkingSpaces,
    this.garageSpaces,
    this.cellarSurface,
    this.atticSurface,
    this.constructionYear,
    this.renovationYear,
    this.heatingType,
    this.hotWaterType,
    this.energyClass,
    this.energyConsumption,
    this.orientation,
    required this.furnished,
    required this.petsAllowed,
    required this.smokingAllowed,
    required this.elevator,
    required this.hasBalcony,
    required this.hasParking,
    required this.airConditioning,
    required this.heating,
    required this.hotWater,
    required this.internet,
    required this.cable,
    required this.dishwasher,
    required this.washingMachine,
    required this.dryer,
    required this.refrigerator,
    required this.oven,
    required this.microwave,
    required this.stove,
    this.description,
    this.equipmentList,
    this.country,
    this.region,
    this.district,
    this.latitude,
    this.longitude,
    this.photos,
    this.videoUrl,
  });

  factory PropertyModel.fromJson(Map<String, dynamic> json) {
    return PropertyModel(
      id: json['id'] as int,
      reference: json['reference'] as String,
      name: json['name'] as String,
      address: json['address'] as String,
      city: json['city'] as String,
      postalCode: json['postalCode'] as String,
      fullAddress: json['fullAddress'] as String,
      type: json['type'] as String,
      surface: json['surface'] as int?,
      rooms: json['rooms'] as int?,
      bedrooms: json['bedrooms'] as int?,
      bathrooms: json['bathrooms'] as int?,
      toilets: json['toilets'] as int?,
      floor: json['floor'] as int?,
      totalFloors: json['totalFloors'] as int?,
      balconies: json['balconies'] as int?,
      terraceSurface: (json['terraceSurface'] as num?)?.toDouble(),
      gardenSurface: (json['gardenSurface'] as num?)?.toDouble(),
      landSurface: (json['landSurface'] as num?)?.toDouble(),
      parkingSpaces: json['parkingSpaces'] as int?,
      garageSpaces: json['garageSpaces'] as int?,
      cellarSurface: (json['cellarSurface'] as num?)?.toDouble(),
      atticSurface: (json['atticSurface'] as num?)?.toDouble(),
      constructionYear: json['constructionYear'] as int?,
      renovationYear: json['renovationYear'] as int?,
      heatingType: json['heatingType'] as String?,
      hotWaterType: json['hotWaterType'] as String?,
      energyClass: json['energyClass'] as String?,
      energyConsumption: json['energyConsumption'] as int?,
      orientation: json['orientation'] as String?,
      furnished: json['furnished'] as bool,
      petsAllowed: json['petsAllowed'] as bool,
      smokingAllowed: json['smokingAllowed'] as bool,
      elevator: json['elevator'] as bool,
      hasBalcony: json['hasBalcony'] as bool,
      hasParking: json['hasParking'] as bool,
      airConditioning: json['airConditioning'] as bool,
      heating: json['heating'] as bool,
      hotWater: json['hotWater'] as bool,
      internet: json['internet'] as bool,
      cable: json['cable'] as bool,
      dishwasher: json['dishwasher'] as bool,
      washingMachine: json['washingMachine'] as bool,
      dryer: json['dryer'] as bool,
      refrigerator: json['refrigerator'] as bool,
      oven: json['oven'] as bool,
      microwave: json['microwave'] as bool,
      stove: json['stove'] as bool,
      description: json['description'] as String?,
      equipmentList: (json['equipmentList'] as List?)?.map((e) => e as String).toList(),
      country: json['country'] as String?,
      region: json['region'] as String?,
      district: json['district'] as String?,
      latitude: (json['latitude'] as num?)?.toDouble(),
      longitude: (json['longitude'] as num?)?.toDouble(),
      photos: (json['photos'] as List?)?.map((e) => e as String).toList(),
      videoUrl: json['videoUrl'] as String?,
    );
  }
}

// lib/models/profession.dart

class Profession {
  final String name;  // الاسم الذي يظهر للمستخدم
  final String query; // القيمة التي تحفظ في قاعدة البيانات

  const Profession({required this.name, required this.query});
}
# TLN Rating System - ACF Fields

## Fields to Add (to Posts)

Add these to your ACF field group:

| Field Label | Field Name | Type | Default |
|-------------|------------|------|---------|
| Rating Quality | rating_quality | Number (1-5) | 0 |
| Rating Value | rating_value | Number (1-5) | 0 |
| Rating Service | rating_service | Number (1-5) | 0 |
| Rating Experience | rating_experience | Number (1-5) | 0 |
| Total Ratings Count | rating_count | Number | 0 |
| Rating Overall | rating_overall | Number | 0 |

## Display Calculation

When displaying, calculate percentage:
- 1 = 20%
- 2 = 40%
- 3 = 60%
- 4 = 80%
- 5 = 100%

Formula in Skill Bar: [rating_X] * 20

## Title to Display
"What Neighbors Say"

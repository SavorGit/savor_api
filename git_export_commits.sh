#!/bin/bash  
  
# 获取输入的开始日期和结束日期  
echo "请输入开始日期 (格式：YYYY-MM-DD)："  
read start_date  
echo "请输入结束日期 (格式：YYYY-MM-DD)："  
read end_date  
  
# 进入本地存储库目录  
#cd /Applications/XAMPP/xamppfiles/htdocs/www/savorGit/savor_api
  
# 获取指定时间范围内的提交记录，并将输出导入到文件  
git log --since="$start_date" --until="$end_date" --name-only > "$start_date-$end_date-commits.txt"
  
# 显示导出结果  
echo "导出成功！导出时间段内的提交记录已保存到$start_date-$end_date-commits.txt文件中。"
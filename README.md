# moodle_filter_cssinject

This simple filter allows users to add css to their content, without switching to code-view. It also provides pre-made css-classes to visually enhance basic text areas.

![image](https://github.com/user-attachments/assets/92219092-8bf0-4da8-a825-652f91e66c93)


## Usage examples

Use `[!box: ... !]` to directly apply a bordered css-style around the content. There are several classes that can be used. Classes of a different category can also be combined (e.g.: [!box: info purple!])

To limit the box to just a part of the content, the tags `[!box-start: ... !]` and `[!box-end!]` can be used.
```
[!box: info red!]
[!box-start: read!] This is important to read! [!box-end!]
```
Currently available classes:
```
// pre-made classes
info
read
warning
stop

// icons
icon_lamp
icon_book
icon_warning
icon_stop

// colors
gray
red
green
blue
yellow
orange
purple
```

Use `[!style: ... !]` to directly apply css-styles to a new div around the content. 
```
[!style: color:red; font-weight:bold; !]
```

Use `[!class: ... !]` to directly apply css-classes to a new div around the content.
```
[!class: my_awesome_class !]
```

Use `[!page: ... !]` to directly add pure css to the whole page. It gets injected using a style element.
```
[!page: 
  * {
    font-family: "Times New Roman", Times, serif;
  }
!]
```

Use `[!: ... !]` and `[!!]` to directly apply css to a new span around a part of the content.
```
[!: color:red; font-weight:bold; !] Attention! [!!]
```

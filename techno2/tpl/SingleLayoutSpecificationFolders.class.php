<div style="display:inline-block">
    [#SingleToolbar#]
    <div class='folder-cover [#STATE_CLASS#]'>
        <div class='inner-folder'>
            <div class='block_title [#STATE_CLASS#] special-title'>[#SingleIcon#] |Спецификация |* « [#name#] »</div>
             <!--ET_BEGIN detailBlock-->
            <fieldset class="detail-info">
                <legend class="groupTitle">|Детайли|*</legend>
                [#detailBlock#]
                <div class="groupList">
                    <table>
                        <!--ET_BEGIN description-->
                        <tr>
                            <td class='dt'>|Описание|*:</td>
                            <td>[#description#]</td>
                        </tr>
                        <!--ET_END description-->
                    </table>
                </div>
            </fieldset>
            <!--ET_END detailBlock-->
            <!--ET_BEGIN DETAILS-->
            [#DETAILS#]
            <!--ET_END DETAILS-->
            <div style='clear: both;'></div>
        </div>
    </div>
</div> 